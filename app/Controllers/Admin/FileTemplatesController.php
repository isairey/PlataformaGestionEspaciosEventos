<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

class FileTemplatesController extends BaseController
{
    protected $templatesPath;

    public function __construct()
    {
        $this->templatesPath = FCPATH . 'assets/templates/';
    }

    /**
     * Display file templates management page
     */
    public function index()
    {
        // Check if user is logged in and is admin
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'admin') {
            return redirect()->to('/login');
        }

        $templates = $this->getTemplateFiles();
        
        // Load saved signatory data for each template
        foreach ($templates as &$template) {
            $template['signatories'] = $this->getSignatoriesForTemplate($template['name']);
        }

        $data = [
            'title' => 'File Templates Management',
            'templates' => $templates
        ];

        return view('admin/file_templates', $data);
    }

    /**
     * Get all template files (AJAX)
     */
    public function getTemplates()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid request']);
        }

        try {
            $templates = $this->getTemplateFiles();

            return $this->response->setJSON([
                'success' => true,
                'data' => $templates
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Template fetch error: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Failed to fetch templates'
            ]);
        }
    }

    /**
     * Update/Replace a template file
     */
    public function updateTemplate()
    {
        try {
            $templateName = $this->request->getPost('template_name');
            $file = $this->request->getFile('template_file');

            // Log the request details
            log_message('info', 'Template update request received for: ' . $templateName);

            if (!$templateName) {
                return $this->response->setStatusCode(400)->setJSON([
                    'success' => false,
                    'message' => 'Template name is required'
                ]);
            }

            if (!$file || !$file->isValid()) {
                $error = $file ? $file->getErrorString() : 'No file uploaded';
                log_message('error', 'File validation failed: ' . $error);
                return $this->response->setStatusCode(400)->setJSON([
                    'success' => false,
                    'message' => 'Please select a valid file: ' . $error
                ]);
            }

            // Validate file extension matches the original
            $originalPath = $this->templatesPath . $templateName;
            if (!file_exists($originalPath)) {
                log_message('error', 'Original template not found: ' . $originalPath);
                return $this->response->setStatusCode(404)->setJSON([
                    'success' => false,
                    'message' => 'Original template file not found'
                ]);
            }

            $originalExt = pathinfo($templateName, PATHINFO_EXTENSION);
            $uploadedExt = $file->getExtension();

            if (strtolower($originalExt) !== strtolower($uploadedExt)) {
                log_message('error', "Extension mismatch: expected .{$originalExt}, got .{$uploadedExt}");
                return $this->response->setStatusCode(400)->setJSON([
                    'success' => false,
                    'message' => "File extension must be .{$originalExt}"
                ]);
            }

            // Validate file size (max 10MB)
            if ($file->getSize() > 10485760) {
                log_message('error', 'File size exceeds limit: ' . $file->getSize() . ' bytes');
                return $this->response->setStatusCode(400)->setJSON([
                    'success' => false,
                    'message' => 'File size must not exceed 10MB'
                ]);
            }

            // Create backup of the original file
            $backupPath = $this->templatesPath . 'backups/';
            if (!is_dir($backupPath)) {
                mkdir($backupPath, 0755, true);
                log_message('info', 'Created backup directory: ' . $backupPath);
            }

            $backupFile = $backupPath . pathinfo($templateName, PATHINFO_FILENAME) . '_' . date('Ymd_His') . '.' . $originalExt;

            // Create backup using file_get_contents/file_put_contents
            log_message('info', 'Creating backup: ' . $backupFile);
            $fileContents = file_get_contents($originalPath);
            if ($fileContents === false) {
                throw new \Exception('Failed to read original file for backup');
            }

            if (file_put_contents($backupFile, $fileContents) === false) {
                throw new \Exception('Failed to create backup file');
            }
            log_message('info', 'Backup created successfully');

            // Alternative approach: Use rename with temporary file
            // This avoids the need to delete and works better on Windows
            $tempName = pathinfo($templateName, PATHINFO_FILENAME) . '_temp_' . time() . '.' . $originalExt;

            log_message('info', 'Moving uploaded file to temporary location: ' . $tempName);

            // Move uploaded file to temp location first
            if (!$file->move($this->templatesPath, $tempName, true)) {
                throw new \Exception('Failed to move uploaded file to temporary location');
            }

            $tempPath = $this->templatesPath . $tempName;

            // Clear cache
            clearstatcache(true, $originalPath);
            clearstatcache(true, $tempPath);

            // Check if we can write to the original file
            if (!is_writable($originalPath)) {
                log_message('error', 'Original file is not writable: ' . $originalPath);
                // Clean up temp file
                @unlink($tempPath);
                throw new \Exception('Original template file is not writable. It may be open in another program.');
            }

            log_message('info', 'Attempting to replace original file with new upload');

            // Try to replace the file using file operations instead of unlink
            $newContents = file_get_contents($tempPath);
            if ($newContents === false) {
                @unlink($tempPath);
                throw new \Exception('Failed to read uploaded file');
            }

            // Overwrite the original file
            if (file_put_contents($originalPath, $newContents) === false) {
                @unlink($tempPath);
                throw new \Exception('Failed to overwrite original template. The file may be open in another program.');
            }

            // Remove temporary file
            @unlink($tempPath);

            log_message('info', 'Template updated successfully: ' . $templateName);

            // Get updated file info
            clearstatcache(true, $originalPath);
            $updatedTemplate = $this->getFileInfo($templateName);

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Template updated successfully',
                'data' => $updatedTemplate
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Template update error: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => $e->getMessage(),
                'details' => 'Check if the file is open in Word, Excel, or another program and close it before uploading.'
            ]);
        }
    }

    /**
     * Download a template file
     */
    public function downloadTemplate($filename)
    {
        try {
            $filepath = $this->templatesPath . $filename;

            if (!file_exists($filepath)) {
                return $this->response->setStatusCode(404)->setJSON([
                    'success' => false,
                    'message' => 'Template file not found'
                ]);
            }

            return $this->response->download($filepath, null)->setFileName($filename);

        } catch (\Exception $e) {
            log_message('error', 'Template download error: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Failed to download template'
            ]);
        }
    }

    /**
     * Get all template files with their info
     */
    private function getTemplateFiles()
    {
        $templates = [];

        if (!is_dir($this->templatesPath)) {
            return $templates;
        }

        $files = scandir($this->templatesPath);

        foreach ($files as $file) {
            if ($file === '.' || $file === '..' || $file === 'backups') {
                continue;
            }

            $filepath = $this->templatesPath . $file;

            if (is_file($filepath)) {
                $templates[] = $this->getFileInfo($file);
            }
        }

        return $templates;
    }

    /**
     * Get file information
     */
    private function getFileInfo($filename)
    {
        $filepath = $this->templatesPath . $filename;

        return [
            'name' => $filename,
            'display_name' => $this->formatDisplayName($filename),
            'size' => filesize($filepath),
            'size_formatted' => $this->formatFileSize(filesize($filepath)),
            'modified' => filemtime($filepath),
            'modified_formatted' => date('F d, Y g:i A', filemtime($filepath)),
            'extension' => pathinfo($filename, PATHINFO_EXTENSION),
            'type' => $this->getFileType($filename)
        ];
    }

    /**
     * Format display name from filename
     */
    private function formatDisplayName($filename)
    {
        $name = pathinfo($filename, PATHINFO_FILENAME);
        $name = str_replace('_', ' ', $name);
        $name = ucwords($name);
        return $name;
    }

    /**
     * Format file size
     */
    private function formatFileSize($bytes)
    {
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' B';
        }
    }

    /**
     * Get signatory data for a specific template
     */
    private function getSignatoriesForTemplate($templateName)
    {
        $storagePath = FCPATH . 'writable/signatories/';
        $filename = pathinfo($templateName, PATHINFO_FILENAME) . '_signatories.json';
        $filepath = $storagePath . $filename;

        $defaultData = [
            'signatory_1' => '',
            'signatory_1_title' => '',
            'signatory_2' => '',
            'signatory_2_title' => '',
            'signatory_3' => '',
            'signatory_3_title' => '',
            'additional_info' => '',
            'saved_at' => null,
            'saved_by' => null
        ];

        if (file_exists($filepath)) {
            try {
                $jsonData = json_decode(file_get_contents($filepath), true);
                return array_merge($defaultData, $jsonData ?? []);
            } catch (\Exception $e) {
                log_message('error', 'Error reading signatories file: ' . $e->getMessage());
                return $defaultData;
            }
        }

        return $defaultData;
    }

    /**
     * Save signatory data for a specific template
     */
    private function saveSignatoriesToFile($templateName, $data)
    {
        $storagePath = FCPATH . 'writable/signatories/';
        
        // Create directory if it doesn't exist
        if (!is_dir($storagePath)) {
            @mkdir($storagePath, 0755, true);
        }

        $filename = pathinfo($templateName, PATHINFO_FILENAME) . '_signatories.json';
        $filepath = $storagePath . $filename;
        
        $signatoriesData = [
            'template_name' => $templateName,
            'signatory_1' => $data['signatory_1'] ?? '',
            'signatory_1_title' => $data['signatory_1_title'] ?? '',
            'signatory_2' => $data['signatory_2'] ?? '',
            'signatory_2_title' => $data['signatory_2_title'] ?? '',
            'signatory_3' => $data['signatory_3'] ?? '',
            'signatory_3_title' => $data['signatory_3_title'] ?? '',
            'additional_info' => $data['additional_info'] ?? '',
            'saved_at' => date('Y-m-d H:i:s'),
            'saved_by' => session('full_name') ?? 'System'
        ];

        return file_put_contents($filepath, json_encode($signatoriesData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Get signatory data for a specific template (AJAX)
     */
    public function getSignatories()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid request']);
        }

        $templateName = $this->request->getPost('template_name');

        if (!$templateName) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'Template name is required'
            ]);
        }

        try {
            $signatories = $this->getSignatoriesForTemplate($templateName);
            return $this->response->setJSON([
                'success' => true,
                'data' => $signatories
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error fetching signatories: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Failed to fetch signatory data'
            ]);
        }
    }

    /**
     * Save signatory information for templates
     */
    public function saveSignatories()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid request']);
        }

        try {
            $json = $this->request->getJSON();
            $templates = $json->templates ?? [];

            if (empty($templates)) {
                return $this->response->setStatusCode(400)->setJSON([
                    'success' => false,
                    'message' => 'No template data provided'
                ]);
            }

            $savedCount = 0;

            // Save each template's signatory information
            foreach ($templates as $filename => $data) {
                if ($this->saveSignatoriesToFile($filename, (array)$data)) {
                    $savedCount++;
                    log_message('info', 'Signatories saved for template: ' . $filename . ' by ' . (session('full_name') ?? 'System'));
                } else {
                    log_message('error', 'Failed to save signatories for template: ' . $filename);
                }
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Signatory information saved successfully!',
                'saved_count' => $savedCount,
                'total_count' => count($templates)
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Signatory save error: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Failed to save signatory information',
                'details' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get template configuration (signatories layout)
     */
    public function getTemplateConfig()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid request']);
        }

        $json = $this->request->getJSON();
        $templateName = $json->template_name ?? null;

        if (!$templateName) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'Template name is required'
            ]);
        }

        try {
            $config = $this->getTemplateSignatoriesConfig($templateName);
            
            if (!$config) {
                return $this->response->setStatusCode(404)->setJSON([
                    'success' => false,
                    'message' => 'Template configuration not found'
                ]);
            }

            return $this->response->setJSON([
                'success' => true,
                'data' => $config
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error fetching template config: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Failed to fetch template configuration'
            ]);
        }
    }

    /**
     * Get MOA signatories configuration from config
     */
    private function getMoaSignatoriesConfig($templateName)
    {
        $moaConfig = config('MoaSignatories');
        
        try {
            $signatoriesModel = model('SignatoriesModel');
            $dbValues = $signatoriesModel->getByTemplate('moa_template.docx');
            
            // Build lookup array from database
            $dbLookup = [];
            if ($dbValues) {
                foreach ($dbValues as $record) {
                    $dbLookup[$record['field_key']] = $record['field_value'];
                }
            }
            
            log_message('debug', 'MOA DB Values: ' . json_encode($dbValues));
            log_message('debug', 'MOA DB Lookup: ' . json_encode($dbLookup));
        } catch (\Exception $e) {
            log_message('error', 'Error reading MOA signatories from database: ' . $e->getMessage());
            $dbLookup = [];
        }
        
        $signatories = [];
        foreach ($moaConfig::SIGNATORIES as $key => $sig) {
            // Get value from database, fall back to default
            $currentValue = $dbLookup[$sig['marker']] ?? $sig['default_name'];
            
            log_message('debug', 'Signatory ' . $sig['marker'] . ': ' . $currentValue);
            
            $signatories[] = [
                'label' => $sig['label'],
                'placeholder' => $sig['placeholder'],
                'cell_location' => 'Marker: ' . $sig['marker'],
                'current_value' => $currentValue
            ];
        }
        
        return ['signatories' => $signatories];
    }

    /**
     * Get template signatories configuration
     */
    private function getTemplateSignatoriesConfig($templateName)
    {
        // Get the display name (without extension, formatted)
        $baseName = strtolower(pathinfo($templateName, PATHINFO_FILENAME));
        
        log_message('info', 'Looking for template config: ' . $baseName . ' (from: ' . $templateName . ')');
        
        // Define signatory configurations for each template (key by lowercase base name)
        $configs = [
            'billing_statement_template' => [
                'signatories' => [
                    [
                        'label' => 'Prepared By',
                        'subtitle' => 'Supply and Property In-charge',
                        'placeholder' => 'Enter signatory name',
                        'cell_location' => 'Cell: D48:F48 (Merged)',
                        'current_value' => $this->getExcelCellValue($templateName, 'D48')
                    ]
                ]
            ],
            'equipment_request_form_template' => [
                'signatories' => [
                    [
                        'label' => 'Admin. Officer V/ Supply',
                        'placeholder' => 'Enter signatory name',
                        'cell_location' => 'Cell: C63:E63 (Merged)',
                        'current_value' => $this->getExcelCellValue($templateName, 'C63')
                    ],
                    [
                        'label' => 'VP for Admin',
                        'placeholder' => 'Enter signatory name',
                        'cell_location' => 'Cell: C68:E68 (Merged)',
                        'current_value' => $this->getExcelCellValue($templateName, 'C68')
                    ]
                ]
            ],
            'inspection_evaluation_template' => [
                'signatories' => [
                    [
                        'label' => 'Gen. Services Aide/ AA II',
                        'placeholder' => 'Enter signatory name',
                        'cell_location' => 'Cell: A36',
                        'current_value' => $this->getExcelCellValue($templateName, 'A36')
                    ]
                ]
            ],
            'order_of_payment_template' => [
                'signatories' => [
                    [
                        'label' => 'Signature over Printed Name Head of Supply and Property Division/Unit/Authorized Official',
                        'placeholder' => 'Enter signatory name',
                        'cell_location' => 'Cell: E36:H36 (Merged)',
                        'current_value' => $this->getExcelCellValue($templateName, 'E36')
                    ]
                ]
            ],
            'moa_template' => $this->getMoaSignatoriesConfig($templateName),
            // Add more templates here as you provide them
        ];

        $result = $configs[$baseName] ?? null;
        log_message('info', 'Config found: ' . ($result ? 'YES' : 'NO'));
        
        return $result;
    }

    /**
     * Get value from Excel cell
     */
    private function getExcelCellValue($templateName, $cellAddress)
    {
        try {
            $filepath = $this->templatesPath . $templateName;
            
            if (!file_exists($filepath)) {
                return '';
            }

            $extension = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
            
            // For Word documents, search for placeholder text
            if ($extension === 'docx') {
                return $this->getWordDocumentValue($filepath, $cellAddress);
            }
            
            // For Excel files, use cell reference
            if (class_exists('\PhpOffice\PhpSpreadsheet\IOFactory')) {
                $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
                $reader->setReadDataOnly(true); // Only read data, not formatting
                $spreadsheet = $reader->load($filepath);
                $worksheet = $spreadsheet->getActiveSheet();
                $cellValue = $worksheet->getCell($cellAddress)->getValue();
                
                // Properly close the spreadsheet
                $spreadsheet->disconnectWorksheets();
                
                return $cellValue ?? '';
            }
            
            return '';
        } catch (\Exception $e) {
            log_message('error', 'Error reading Excel cell: ' . $e->getMessage());
            return '';
        }
    }

    /**
     * Read placeholder text from Word document by marker
     */
    private function getWordDocumentValue($filepath, $placeholder)
    {
        try {
            if (!class_exists('ZipArchive')) {
                return '';
            }

            $zip = new \ZipArchive();
            if ($zip->open($filepath) !== true) {
                return '';
            }

            // Read document.xml from the Word file
            $xml = $zip->getFromName('word/document.xml');
            $zip->close();

            if ($xml === false) {
                return '';
            }

            // For marker-based placeholders (e.g., ###VP_FINANCE###)
            if (strpos($placeholder, '###') === 0) {
                // Try multiple patterns to find marker and its associated text
                
                // Pattern 1: Marker and value in same text run
                $pattern = '/<w:t>' . preg_quote($placeholder, '/') . '\s*([^<]*)<\/w:t>/s';
                if (preg_match($pattern, $xml, $matches)) {
                    $value = trim($matches[1]);
                    if (!empty($value)) {
                        return $value;
                    }
                }
                
                // Pattern 2: Marker in one run, value in next run
                $pattern = '/<w:t>' . preg_quote($placeholder, '/') . '<\/w:t>.*?<w:t>([^<]+)<\/w:t>/s';
                if (preg_match($pattern, $xml, $matches)) {
                    $value = trim($matches[1]);
                    if (!empty($value)) {
                        return $value;
                    }
                }
                
                // Pattern 3: Search for marker with closing tag, then find next text
                $pattern = '/<w:t>' . preg_quote($placeholder, '/') . '<\/w:t>\s*(?:<\/w:r>)?.*?<w:r>.*?<w:t>([^<]+)<\/w:t>/s';
                if (preg_match($pattern, $xml, $matches)) {
                    $value = trim($matches[1]);
                    if (!empty($value)) {
                        return $value;
                    }
                }
                
                return '';
            }

            // For direct text lookup (fallback)
            $pattern = '/<w:t>(' . preg_quote($placeholder, '/') . ')<\/w:t>/i';
            
            if (preg_match($pattern, $xml, $matches)) {
                return $matches[1];
            }

            return '';
        } catch (\Exception $e) {
            log_message('error', 'Error reading Word document value: ' . $e->getMessage());
            return '';
        }
    }

    /**
     * Update signatories in template file
     */
    public function updateSignatories()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid request']);
        }

        try {
            $json = $this->request->getJSON();
            $templateName = $json->template_name ?? null;
            $signatories = (array)($json->signatories ?? []);  // Convert object to array

            if (!$templateName) {
                return $this->response->setStatusCode(400)->setJSON([
                    'success' => false,
                    'message' => 'Template name is required'
                ]);
            }

            $filepath = $this->templatesPath . $templateName;

            if (!file_exists($filepath)) {
                return $this->response->setStatusCode(404)->setJSON([
                    'success' => false,
                    'message' => 'Template file not found'
                ]);
            }

            // Save to database AND update template file
            try {
                $signatoriesModel = model('SignatoriesModel');
                $moaConfig = config('MoaSignatories');
                
                // Get the signatory fields
                $cellConfigs = $this->getSignatoryCells($templateName);
                
                if ($cellConfigs && is_array($signatories)) {
                    foreach ($cellConfigs as $index => $cellConfig) {
                        $value = $signatories[$index] ?? '';
                        
                        if (!empty($value)) {
                            // Get field key
                            $fieldKey = $cellConfig['find'] ?? $cellConfig['cell'] ?? null;
                            
                            if ($fieldKey) {
                                // Save to database
                                $signatoriesModel->setValue($templateName, $fieldKey, $fieldKey, $value);
                                log_message('debug', 'Saved signatory ' . $fieldKey . ' = ' . $value);
                            }
                        }
                    }
                }
                
                // Update the actual template file with signatory values
                $updateResult = $this->updateExcelSignatories($templateName, $signatories);
                
                if (!$updateResult) {
                    log_message('warning', 'Template file update partially failed for: ' . $templateName . ', but database was updated');
                }
                
                log_message('info', 'Signatories saved to database and template file: ' . $templateName . ' by ' . (session('full_name') ?? 'System'));
                
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Signatories updated successfully!'
                ]);
                
            } catch (\Exception $e) {
                log_message('error', 'Exception in database/template save: ' . $e->getMessage());
                return $this->response->setStatusCode(500)->setJSON([
                    'success' => false,
                    'message' => 'Failed to save signatories to database and template',
                    'details' => $e->getMessage()
                ]);
            }

        } catch (\Exception $e) {
            log_message('error', 'Error updating signatories: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Failed to update signatories',
                'details' => $e->getMessage()
            ]);
        }
    }

    /**
     * Create backup of template
     */
    private function createTemplateBackup($templateName, $filepath)
    {
        try {
            $backupPath = $this->templatesPath . 'backups/';
            if (!is_dir($backupPath)) {
                mkdir($backupPath, 0755, true);
            }

            $ext = pathinfo($templateName, PATHINFO_EXTENSION);
            $name = pathinfo($templateName, PATHINFO_FILENAME);
            $backupFile = $backupPath . $name . '_' . date('Ymd_His') . '.' . $ext;
            
            copy($filepath, $backupFile);
            log_message('info', 'Backup created: ' . $backupFile);
        } catch (\Exception $e) {
            log_message('warning', 'Failed to create backup: ' . $e->getMessage());
        }
    }

    /**
     * Update Excel file with signatory values
     */
    private function updateExcelSignatories($templateName, $signatories)
    {
        try {
            $filepath = $this->templatesPath . $templateName;
            
            if (!file_exists($filepath)) {
                return false;
            }

            // Get the signatory cell configurations (now returns array of cells)
            $cellConfigs = $this->getSignatoryCells($templateName);
            
            if (!$cellConfigs) {
                throw new \Exception('No cell configuration found for template: ' . $templateName);
            }

            // Convert object to array if needed
            $signatoriesArray = is_object($signatories) ? (array)$signatories : $signatories;
            
            // Update each cell/placeholder in the file
            foreach ($cellConfigs as $index => $cellConfig) {
                $value = $signatoriesArray[$index] ?? '';
                if (!empty($value)) {
                    $format = $cellConfig['format'] ?? [];
                    
                    // Use 'find' for Word documents, 'cell' for Excel
                    $identifier = $cellConfig['find'] ?? $cellConfig['cell'] ?? null;
                    
                    if ($identifier) {
                        $result = $this->setExcelCellValue($filepath, $identifier, $value, $format);
                        if (!$result) {
                            return false;
                        }
                    }
                }
            }
            
            return true;

        } catch (\Exception $e) {
            log_message('error', 'Error updating Excel signatories: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get MOA signatory fields for cell update
     */
    private function getMoaSignatoryFields()
    {
        $moaConfig = config('MoaSignatories');
        
        $fields = [];
        foreach ($moaConfig::SIGNATORIES as $sig) {
            $fields[] = [
                'find' => $sig['marker'],
                'format' => $sig['format']
            ];
        }
        
        return $fields;
    }

    /**
     * Get the signatory cell configuration for a template
     */
    private function getSignatoryCells($templateName)
    {
        $baseName = strtolower(pathinfo($templateName, PATHINFO_FILENAME));
        
        $cellConfigs = [
            'billing_statement_template' => [
                ['cell' => 'D48', 'format' => ['bold' => true, 'uppercase' => true]]
            ],
            'equipment_request_form_template' => [
                ['cell' => 'C63', 'format' => ['bold' => true, 'uppercase' => true]],
                ['cell' => 'C68', 'format' => ['bold' => true, 'uppercase' => true]]
            ],
            'inspection_evaluation_template' => [
                ['cell' => 'A36', 'format' => ['bold' => true, 'uppercase' => false]]
            ],
            'order_of_payment_template' => [
                ['cell' => 'E36', 'format' => ['bold' => true, 'uppercase' => true]]
            ],
            'moa_template' => $this->getMoaSignatoryFields(),
            // Add more template cell configurations here
        ];

        return $cellConfigs[$baseName] ?? null;
    }

    /**
     * Set value in Excel cell with formatting
     */
    private function setExcelCellValue($filepath, $cellAddress, $value, $format = [])
    {
        // Detect file type and route to appropriate handler
        $extension = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
        
        if ($extension === 'docx') {
            return $this->setWordDocumentValue($filepath, $cellAddress, $value, $format);
        }
        
        return $this->setExcelValue($filepath, $cellAddress, $value, $format);
    }

    /**
     * Update Excel cell value via ZIP manipulation
     */
    private function setExcelValue($filepath, $cellAddress, $value, $format = [])
    {
        try {
            // Validate file exists
            if (!file_exists($filepath)) {
                throw new \Exception('File does not exist: ' . $filepath);
            }

            // Validate file is readable and writable
            if (!is_readable($filepath)) {
                throw new \Exception('File is not readable: ' . $filepath);
            }

            if (!is_writable($filepath)) {
                throw new \Exception('File is not writable: ' . $filepath);
            }

            // Check if we have ZipArchive
            if (!class_exists('ZipArchive')) {
                throw new \Exception('ZipArchive extension not available');
            }

            // Apply formatting rules
            $cellValue = trim($value);
            if ($format['uppercase'] ?? false) {
                $cellValue = strtoupper($cellValue);
            }
            
            log_message('debug', 'Setting cell ' . $cellAddress . ' to value: ' . $cellValue);

            // Create a backup before modification
            $backupPath = $filepath . '.backup_' . uniqid();
            if (!copy($filepath, $backupPath)) {
                throw new \Exception('Failed to create backup during cell update');
            }

            try {
                // Open the XLSX file as a ZIP
                $zip = new \ZipArchive();
                if ($zip->open($filepath) !== true) {
                    throw new \Exception('Failed to open XLSX file as ZIP');
                }

                // Read the worksheet XML
                $xmlFile = 'xl/worksheets/sheet1.xml';
                $xml = $zip->getFromName($xmlFile);

                if ($xml === false) {
                    $zip->close();
                    throw new \Exception('Could not read worksheet from XLSX file');
                }

                // Use simple regex to find and replace the cell value
                // Pattern: <c r="D48"...><v>OLD_VALUE</v></c>
                $pattern = '/<c\s+r="' . preg_quote($cellAddress, '/') . '"([^>]*)>\s*<v>.*?<\/v>\s*<\/c>/s';
                
                // Check if cell exists
                if (!preg_match($pattern, $xml)) {
                    log_message('warning', 'Cell ' . $cellAddress . ' not found in worksheet');
                    $zip->close();
                    @unlink($backupPath);
                    return true;
                }

                // Replace the value using regex
                $newXml = preg_replace_callback(
                    '/<c\s+r="' . preg_quote($cellAddress, '/') . '"([^>]*)>\s*<v>.*?<\/v>/',
                    function($matches) use ($cellAddress, $cellValue) {
                        // Extract attributes
                        $attrs = $matches[1];
                        
                        // Ensure cell type is "str" for text values
                        if (strpos($attrs, 't="') === false) {
                            $attrs = ' t="str"' . $attrs;
                        } else {
                            $attrs = preg_replace('/t="[^"]*"/', 't="str"', $attrs);
                        }
                        
                        return '<c r="' . $cellAddress . '"' . $attrs . '><v>' . htmlspecialchars($cellValue) . '</v>';
                    },
                    $xml
                );

                if ($newXml === $xml) {
                    log_message('warning', 'Failed to replace cell value in XML');
                    $zip->close();
                    @unlink($backupPath);
                    return true;
                }

                // Update the worksheet in the ZIP
                $zip->addFromString($xmlFile, $newXml);

                // Close the ZIP properly
                if ($zip->close() !== true) {
                    throw new \Exception('Failed to close XLSX file after modifications');
                }

                // Verify file integrity by checking size
                if (filesize($filepath) === 0) {
                    // Restore from backup
                    copy($backupPath, $filepath);
                    throw new \Exception('Modified XLSX file is empty, restored from backup');
                }

                // Clean up backup
                @unlink($backupPath);

                log_message('info', 'Successfully updated Excel cell: ' . $cellAddress . ' in ' . $filepath);
                return true;

            } catch (\Exception $e) {
                // Restore from backup on error
                if (file_exists($backupPath)) {
                    copy($backupPath, $filepath);
                    @unlink($backupPath);
                }
                throw $e;
            }

        } catch (\Exception $e) {
            log_message('error', 'Error updating Excel cell: ' . $e->getMessage());
            log_message('error', 'File path: ' . $filepath);
            log_message('error', 'Cell address: ' . $cellAddress);
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            throw $e;
        }
    }

    /**
     * Update Word document placeholder text via ZIP manipulation
     */
    private function setWordDocumentValue($filepath, $placeholder, $value, $format = [])
    {
        try {
            // Validate file exists
            if (!file_exists($filepath)) {
                throw new \Exception('File does not exist: ' . $filepath);
            }

            // Validate file is readable and writable
            if (!is_readable($filepath)) {
                throw new \Exception('File is not readable: ' . $filepath);
            }

            if (!is_writable($filepath)) {
                throw new \Exception('File is not writable: ' . $filepath);
            }

            // Check if we have ZipArchive
            if (!class_exists('ZipArchive')) {
                throw new \Exception('ZipArchive extension not available');
            }

            // Apply formatting rules
            $newValue = trim($value);
            if ($format['uppercase'] ?? false) {
                $newValue = strtoupper($newValue);
            }
            
            log_message('debug', 'Setting Word placeholder "' . $placeholder . '" to value: ' . $newValue);

            // Create a backup before modification
            $backupPath = $filepath . '.backup_' . uniqid();
            if (!copy($filepath, $backupPath)) {
                throw new \Exception('Failed to create backup during document update');
            }

            try {
                // Open the DOCX file as a ZIP
                $zip = new \ZipArchive();
                if ($zip->open($filepath) !== true) {
                    throw new \Exception('Failed to open DOCX file as ZIP');
                }

                // Read the document XML
                $xmlFile = 'word/document.xml';
                $xml = $zip->getFromName($xmlFile);

                if ($xml === false) {
                    $zip->close();
                    throw new \Exception('Could not read document from DOCX file');
                }

                // Handle marker-based placeholders (e.g., ###VP_FINANCE###)
                if (strpos($placeholder, '###') === 0) {
                    // For marker placeholders, replace the text that follows the marker
                    // Pattern: ###MARKER###</w:t>...content...</w:t>
                    // We need to find and replace the content after the marker
                    
                    $escapedMarker = preg_quote($placeholder, '/');
                    
                    // Look for marker followed by any closing tags and then find the next text
                    $pattern = '/' . $escapedMarker . '\s*<\/w:t>(<\/w:r>)?(\s*<w:r>.*?<w:t>).*?(<\/w:t>)/s';
                    
                    if (preg_match($pattern, $xml)) {
                        // Replacement: keep the marker but replace content after it
                        $newXml = preg_replace_callback(
                            $pattern,
                            function($matches) use ($placeholder, $newValue) {
                                // Reconstruct: marker closing tag + structure + new value
                                return $placeholder . '</w:t>' . 
                                       (isset($matches[1]) ? $matches[1] : '') . 
                                       (isset($matches[2]) ? $matches[2] : '<w:r><w:t>') . 
                                       htmlspecialchars($newValue) . '</w:t>';
                            },
                            $xml,
                            1
                        );
                    } else {
                        // Alternative: marker and value might be in the same or adjacent text runs
                        $pattern = '/<w:t>' . $escapedMarker . '([^<]*)<\/w:t>/s';
                        $newXml = preg_replace(
                            $pattern,
                            '<w:t>' . $placeholder . htmlspecialchars($newValue) . '</w:t>',
                            $xml,
                            1
                        );
                    }
                } else {
                    // For direct text replacement (fallback for non-marker placeholders)
                    $escapedPlaceholder = preg_quote($placeholder, '/');
                    $pattern = '/<w:t>' . $escapedPlaceholder . '<\/w:t>/i';
                    
                    $newXml = preg_replace(
                        $pattern,
                        '<w:t>' . htmlspecialchars($newValue) . '</w:t>',
                        $xml,
                        -1,
                        $count
                    );
                    
                    if ($count === 0) {
                        log_message('warning', 'Failed to replace placeholder in document');
                        $zip->close();
                        @unlink($backupPath);
                        return true;
                    }
                }

                // Update the document in the ZIP
                $zip->addFromString($xmlFile, $newXml);

                // Close the ZIP properly
                if ($zip->close() !== true) {
                    throw new \Exception('Failed to close DOCX file after modifications');
                }

                // Verify file integrity by checking size
                if (filesize($filepath) === 0) {
                    // Restore from backup
                    copy($backupPath, $filepath);
                    throw new \Exception('Modified DOCX file is empty, restored from backup');
                }

                // Clean up backup
                @unlink($backupPath);

                log_message('info', 'Successfully updated Word document placeholder: "' . $placeholder . '" in ' . $filepath);
                return true;

            } catch (\Exception $e) {
                // Restore from backup on error
                if (file_exists($backupPath)) {
                    copy($backupPath, $filepath);
                    @unlink($backupPath);
                }
                throw $e;
            }

        } catch (\Exception $e) {
            log_message('error', 'Error updating Word document: ' . $e->getMessage());
            log_message('error', 'File path: ' . $filepath);
            log_message('error', 'Placeholder: ' . $placeholder);
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            throw $e;
        }
    }

    /**
     * Get file type description
     */
    private function getFileType($filename)
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        return match($extension) {
            'xlsx', 'xls' => 'Excel Spreadsheet',
            'docx', 'doc' => 'Word Document',
            'pdf' => 'PDF Document',
            'pptx', 'ppt' => 'PowerPoint Presentation',
            default => 'Document'
        };
    }
}
