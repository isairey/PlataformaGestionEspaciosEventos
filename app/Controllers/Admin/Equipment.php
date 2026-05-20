<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\EquipmentModel;
use CodeIgniter\HTTP\ResponseInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class Equipment extends BaseController
{
    protected $equipmentModel;
    
    public function __construct()
    {
        $this->equipmentModel = new EquipmentModel();
    }

    /**
     * Display equipment management page
     */
    public function index()
    {
        // Check if user is logged in and is admin
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'admin') {
            return redirect()->to('/login');
        }

        $data = [
            'title' => 'Equipment Management',
            'equipment' => $this->equipmentModel->getEquipmentWithStatus()
        ];

        return view('admin/equipment', $data);
    }

    /**
     * Get all equipment (AJAX)
     */
    public function getEquipment()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid request']);
        }

        try {
            $equipment = $this->equipmentModel->getEquipmentWithStatus();
            
            return $this->response->setJSON([
                'success' => true,
                'data' => $equipment
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Equipment fetch error: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Failed to fetch equipment'
            ]);
        }
    }

    /**
     * Get single equipment details (AJAX)
     */
    public function getEquipmentDetails($id)
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid request']);
        }

        try {
            $equipment = $this->equipmentModel->find($id);
            
            if (!$equipment) {
                return $this->response->setStatusCode(404)->setJSON([
                    'success' => false,
                    'message' => 'Equipment not found'
                ]);
            }

            // Calculate additional fields
            $equipment['available'] = max(0, $equipment['good'] - ($equipment['rented'] ?? 0));
            $equipment['status'] = $this->calculateStatus($equipment);

            return $this->response->setJSON([
                'success' => true,
                'data' => $equipment
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Equipment details fetch error: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Failed to fetch equipment details'
            ]);
        }
    }

    /**
     * Add new equipment (AJAX)
     */
    public function addEquipment()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid request']);
        }

        // Get JSON data
        $inputData = $this->request->getJSON(true);

        $validation = \Config\Services::validation();
        $validation->setRules([
            'name' => 'required|min_length[3]|max_length[255]',
            'quantity' => 'required|integer|greater_than[0]',
            'price' => 'required|numeric|greater_than_equal_to[0]',
            'good' => 'required|integer|greater_than_equal_to[0]',
            'damaged' => 'integer|greater_than_equal_to[0]'
        ]);

        if (!$validation->run($inputData)) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validation->getErrors()
            ]);
        }

        try {
            $data = [
                'name' => $inputData['name'],
                'quantity' => (int)$inputData['quantity'],
                'price' => (float)$inputData['price'],
                'good' => (int)$inputData['good'],
                'damaged' => (int)($inputData['damaged'] ?? 0),
                'available' => max(0, (int)$inputData['good'] - 0), // Initially no rented items
                'rented' => 0
            ];

            // Validate that good + damaged = quantity
            if ($data['good'] + $data['damaged'] != $data['quantity']) {
                return $this->response->setStatusCode(400)->setJSON([
                    'success' => false,
                    'message' => 'Good condition + Damaged must equal Total Quantity'
                ]);
            }

            $result = $this->equipmentModel->insert($data);

            if ($result) {
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Equipment added successfully',
                    'data' => $this->equipmentModel->find($result)
                ]);
            } else {
                return $this->response->setStatusCode(500)->setJSON([
                    'success' => false,
                    'message' => 'Failed to add equipment'
                ]);
            }
        } catch (\Exception $e) {
            log_message('error', 'Equipment add error: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'An error occurred while adding equipment'
            ]);
        }
    }

    /**
     * Update equipment (AJAX)
     */
    public function updateEquipment()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid request']);
        }

        try {
            // Get JSON data
            $inputData = $this->request->getJSON(true);
            
            if (!$inputData) {
                return $this->response->setStatusCode(400)->setJSON([
                    'success' => false,
                    'message' => 'No data received'
                ]);
            }

            $id = $inputData['id'] ?? null;

            if (!$id) {
                return $this->response->setStatusCode(400)->setJSON([
                    'success' => false,
                    'message' => 'Equipment ID is required'
                ]);
            }

            $equipment = $this->equipmentModel->find($id);

            if (!$equipment) {
                return $this->response->setStatusCode(404)->setJSON([
                    'success' => false,
                    'message' => 'Equipment not found'
                ]);
            }

            $validation = \Config\Services::validation();
            $validation->setRules([
                'name' => 'required|min_length[3]|max_length[255]',
                'quantity' => 'required|integer|greater_than[0]',
                'price' => 'required|numeric|greater_than_equal_to[0]',
                'good' => 'required|integer|greater_than_equal_to[0]',
                'damaged' => 'integer|greater_than_equal_to[0]'
            ]);

            if (!$validation->run($inputData)) {
                return $this->response->setStatusCode(400)->setJSON([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validation->getErrors()
                ]);
            }

            $data = [
                'name' => $inputData['name'],
                'quantity' => (int)$inputData['quantity'],
                'price' => (float)$inputData['price'],
                'good' => (int)$inputData['good'],
                'damaged' => (int)($inputData['damaged'] ?? 0),
            ];

            // Validate that good + damaged = quantity
            if ($data['good'] + $data['damaged'] != $data['quantity']) {
                return $this->response->setStatusCode(400)->setJSON([
                    'success' => false,
                    'message' => 'Good condition + Damaged must equal Total Quantity'
                ]);
            }

            // Update available quantity (preserve current rented amount)
            $currentRented = $equipment['rented'] ?? 0;
            $data['available'] = max(0, $data['good'] - $currentRented);

            $result = $this->equipmentModel->update($id, $data);

            if ($result) {
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Equipment updated successfully',
                    'data' => $this->equipmentModel->find($id)
                ]);
            } else {
                return $this->response->setStatusCode(500)->setJSON([
                    'success' => false,
                    'message' => 'Failed to update equipment'
                ]);
            }
        } catch (\Exception $e) {
            log_message('error', 'Equipment update error: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'An error occurred while updating equipment: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Delete equipment (AJAX)
     */
    public function deleteEquipment($id)
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid request']);
        }

        try {
            $equipment = $this->equipmentModel->find($id);

            if (!$equipment) {
                return $this->response->setStatusCode(404)->setJSON([
                    'success' => false,
                    'message' => 'Equipment not found'
                ]);
            }

            // Check if equipment is currently rented
            if (($equipment['rented'] ?? 0) > 0) {
                return $this->response->setStatusCode(400)->setJSON([
                    'success' => false,
                    'message' => 'Cannot delete equipment that is currently rented'
                ]);
            }

            $result = $this->equipmentModel->delete($id);

            if ($result) {
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Equipment deleted successfully'
                ]);
            } else {
                return $this->response->setStatusCode(500)->setJSON([
                    'success' => false,
                    'message' => 'Failed to delete equipment'
                ]);
            }
        } catch (\Exception $e) {
            log_message('error', 'Equipment delete error: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'An error occurred while deleting equipment'
            ]);
        }
    }

    /**
     * Generate equipment report as Excel file
     */
    public function generateReport()
    {
        try {
            $equipment = $this->equipmentModel->getEquipmentWithStatus();

            // Calculate summary data
            $totalEquipment = count($equipment);
            $totalValue = array_sum(array_column($equipment, 'price'));
            $totalQuantity = array_sum(array_column($equipment, 'quantity'));
            $totalGood = array_sum(array_column($equipment, 'good'));
            $totalDamaged = array_sum(array_column($equipment, 'damaged'));
            $totalAvailable = array_sum(array_column($equipment, 'available'));
            $totalRented = 0;

            $statusSummary = [
                'good' => 0,
                'damaged' => 0,
                'maintenance' => 0,
                'rented' => 0
            ];

            // Calculate status summary and total rented
            foreach ($equipment as $item) {
                $status = $this->calculateStatus($item);
                $statusSummary[$status]++;
                $totalRented += ($item['rented'] ?? 0);
            }

            // Create new Spreadsheet
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Set document properties
            $spreadsheet->getProperties()
                ->setCreator('CSPC Equipment Management')
                ->setTitle('Equipment Report')
                ->setSubject('Equipment Inventory Report')
                ->setDescription('Comprehensive equipment inventory and status report');

            // Title
            $sheet->setCellValue('A1', 'EQUIPMENT MANAGEMENT REPORT');
            $sheet->mergeCells('A1:H1');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Date
            $sheet->setCellValue('A2', 'Generated on: ' . date('F j, Y g:i A'));
            $sheet->mergeCells('A2:H2');
            $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Summary Section
            $row = 4;
            $sheet->setCellValue('A' . $row, 'SUMMARY STATISTICS');
            $sheet->mergeCells('A' . $row . ':B' . $row);
            $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(12);
            $sheet->getStyle('A' . $row)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB('4472C4');
            $sheet->getStyle('A' . $row)->getFont()->getColor()->setRGB('FFFFFF');

            $row++;
            $summaryData = [
                ['Total Equipment Types:', $totalEquipment],
                ['Total Equipment Quantity:', $totalQuantity],
                ['Total Inventory Value:', '₱' . number_format($totalValue, 2)],
                ['Total Good Condition:', $totalGood],
                ['Total Damaged:', $totalDamaged],
                ['Total Available:', $totalAvailable],
                ['Total Rented:', $totalRented],
            ];

            foreach ($summaryData as $data) {
                $sheet->setCellValue('A' . $row, $data[0]);
                $sheet->setCellValue('B' . $row, $data[1]);
                $sheet->getStyle('A' . $row)->getFont()->setBold(true);
                $row++;
            }

            // Status Summary
            $row++;
            $sheet->setCellValue('A' . $row, 'STATUS BREAKDOWN');
            $sheet->mergeCells('A' . $row . ':B' . $row);
            $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(12);
            $sheet->getStyle('A' . $row)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB('70AD47');
            $sheet->getStyle('A' . $row)->getFont()->getColor()->setRGB('FFFFFF');

            $row++;
            $statusData = [
                ['Good Status:', $statusSummary['good']],
                ['Damaged Status:', $statusSummary['damaged']],
                ['Maintenance Status:', $statusSummary['maintenance']],
                ['Rented Status:', $statusSummary['rented']],
            ];

            foreach ($statusData as $data) {
                $sheet->setCellValue('A' . $row, $data[0]);
                $sheet->setCellValue('B' . $row, $data[1]);
                $sheet->getStyle('A' . $row)->getFont()->setBold(true);
                $row++;
            }

            // Equipment List
            $row += 2;
            $sheet->setCellValue('A' . $row, 'DETAILED EQUIPMENT LIST');
            $sheet->mergeCells('A' . $row . ':H' . $row);
            $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(12);
            $sheet->getStyle('A' . $row)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB('ED7D31');
            $sheet->getStyle('A' . $row)->getFont()->getColor()->setRGB('FFFFFF');

            // Table Headers
            $row++;
            $headers = ['#', 'Equipment Name', 'Total Qty', 'Price (₱)', 'Good', 'Damaged', 'Available', 'Rented', 'Status'];
            $col = 'A';
            foreach ($headers as $header) {
                $sheet->setCellValue($col . $row, $header);
                $sheet->getStyle($col . $row)->getFont()->setBold(true);
                $sheet->getStyle($col . $row)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('D9E1F2');
                $sheet->getStyle($col . $row)->getBorders()->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);
                $col++;
            }

            // Equipment Data
            $row++;
            $startDataRow = $row;
            foreach ($equipment as $index => $item) {
                $status = $this->calculateStatus($item);

                $sheet->setCellValue('A' . $row, $index + 1);
                $sheet->setCellValue('B' . $row, $item['name']);
                $sheet->setCellValue('C' . $row, $item['quantity']);
                $sheet->setCellValue('D' . $row, $item['price']);
                $sheet->setCellValue('E' . $row, $item['good']);
                $sheet->setCellValue('F' . $row, $item['damaged']);
                $sheet->setCellValue('G' . $row, $item['available']);
                $sheet->setCellValue('H' . $row, $item['rented'] ?? 0);
                $sheet->setCellValue('I' . $row, ucfirst($status));

                // Format price as currency
                $sheet->getStyle('D' . $row)->getNumberFormat()
                    ->setFormatCode('#,##0.00');

                // Apply borders to data rows
                $sheet->getStyle('A' . $row . ':I' . $row)->getBorders()->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);

                // Color code status
                $statusColor = match($status) {
                    'good' => 'C6EFCE',
                    'damaged' => 'FFC7CE',
                    'maintenance' => 'FFEB9C',
                    'rented' => 'B4C7E7',
                    default => 'FFFFFF'
                };
                $sheet->getStyle('I' . $row)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB($statusColor);

                $row++;
            }

            // Auto-size columns
            foreach (range('A', 'I') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            // Generate filename
            $filename = 'Equipment_Report_' . date('Y-m-d_His') . '.xlsx';
            $filepath = WRITEPATH . 'temp/' . $filename;

            // Create temp directory if it doesn't exist
            if (!is_dir(WRITEPATH . 'temp/')) {
                mkdir(WRITEPATH . 'temp/', 0755, true);
            }

            // Save the file
            $writer = new Xlsx($spreadsheet);
            $writer->save($filepath);

            // Return file for download
            return $this->response->download($filepath, null)->setFileName($filename);

        } catch (\Exception $e) {
            log_message('error', 'Equipment report error: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Failed to generate report: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Calculate equipment status
     */
    private function calculateStatus($equipment)
    {
        if ($equipment['damaged'] > 0) {
            return 'damaged';
        } elseif (($equipment['rented'] ?? 0) > 0) {
            return 'rented';
        } elseif ($equipment['good'] < $equipment['quantity']) {
            return 'maintenance';
        } else {
            return 'good';
        }
    }
}