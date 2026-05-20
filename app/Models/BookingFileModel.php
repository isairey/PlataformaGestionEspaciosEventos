<?php

namespace App\Models;

use CodeIgniter\Model;

class BookingFileModel extends Model
{
    protected $table = 'booking_files';
    protected $primaryKey = 'id';
protected $allowedFields = [
    'booking_id', 
    'file_type', 
    'original_filename',
    'stored_filename', 
    'file_path', 
    'file_size', 
    'mime_type', 
    'uploaded_by',
    'upload_date',
    'status'
];
    
    protected $useTimestamps = false; // We handle timestamps manually
protected $validationRules = [
    'booking_id' => 'required|integer',
    'file_type' => 'required|max_length[50]',
    'original_filename' => 'required|max_length[255]',
    'stored_filename' => 'required|max_length[255]',
    'file_path' => 'required|max_length[500]',
    'file_size' => 'required|integer',
    'mime_type' => 'required|max_length[100]'
];

    protected $validationMessages = [
        'file_type' => [
            'in_list' => 'Invalid file type. Must be one of: moa_signed, billing_confirmed, equipment_signed, payment_receipt'
        ]
    ];

    /**
     * Get all files for a specific booking
     */
    public function getFilesByBooking($bookingId)
    {
        return $this->where('booking_id', $bookingId)
                   ->orderBy('file_type', 'ASC')
                   ->orderBy('uploaded_at', 'DESC')
                   ->findAll();
    }

    /**
     * Get files by booking and type
     */
    public function getFilesByBookingAndType($bookingId, $fileType)
    {
        return $this->where('booking_id', $bookingId)
                   ->where('file_type', $fileType)
                   ->orderBy('uploaded_at', 'DESC')
                   ->findAll();
    }

    /**
     * Get the latest file of a specific type for a booking
     */
    public function getLatestFileByType($bookingId, $fileType)
    {
        return $this->where('booking_id', $bookingId)
                   ->where('file_type', $fileType)
                   ->orderBy('uploaded_at', 'DESC')
                   ->first();
    }

    /**
     * Check if all required files are uploaded for a booking
     */
    public function areAllFilesUploaded($bookingId, $requiredTypes = null)
    {
        if (!$requiredTypes) {
            $requiredTypes = ['moa_signed', 'billing_confirmed', 'equipment_signed', 'payment_receipt'];
        }

        $uploadedTypes = $this->select('DISTINCT file_type')
                            ->where('booking_id', $bookingId)
                            ->findColumn('file_type');

        return count(array_diff($requiredTypes, $uploadedTypes)) === 0;
    }

    /**
     * Check if all files are approved for a booking
     */
    public function areAllFilesApproved($bookingId, $requiredTypes = null)
    {
        if (!$requiredTypes) {
            $requiredTypes = ['moa_signed', 'billing_confirmed', 'equipment_signed', 'payment_receipt'];
        }

        // Get latest file of each required type
        $approvedCount = 0;
        foreach ($requiredTypes as $type) {
            $latestFile = $this->getLatestFileByType($bookingId, $type);
            if ($latestFile && $latestFile['validation_status'] === 'approved') {
                $approvedCount++;
            }
        }

        return $approvedCount === count($requiredTypes);
    }

    /**
     * Get file validation summary for a booking
     */
    public function getValidationSummary($bookingId)
    {
        $requiredTypes = ['moa_signed', 'billing_confirmed', 'equipment_signed', 'payment_receipt'];
        $summary = [];

        foreach ($requiredTypes as $type) {
            $latestFile = $this->getLatestFileByType($bookingId, $type);
            $summary[$type] = [
                'uploaded' => $latestFile !== null,
                'status' => $latestFile ? $latestFile['validation_status'] : 'not_uploaded',
                'file_info' => $latestFile,
                'validation_notes' => $latestFile ? $latestFile['validation_notes'] : null
            ];
        }

        return $summary;
    }

    /**
     * Upload and store file record
     */
    public function uploadFile($bookingId, $fileType, $fileData, $uploadedByType = 'client')
    {
        // Start transaction
        $this->db->transStart();

        try {
            // Insert file record
            $insertData = [
                'booking_id' => $bookingId,
                'file_type' => $fileType,
                'original_name' => $fileData['original_name'],
                'stored_filename' => $fileData['stored_filename'],
                'file_path' => $fileData['file_path'],
                'file_size' => $fileData['file_size'],
                'mime_type' => $fileData['mime_type'],
                'uploaded_by_type' => $uploadedByType,
                'uploaded_at' => date('Y-m-d H:i:s'),
                'validation_status' => 'pending'
            ];

            $fileId = $this->insert($insertData);

            if ($fileId) {
                // Update booking documents status
                $this->updateBookingDocumentStatus($bookingId);
                
                // Log audit trail
                $this->logFileAudit($fileId, 'uploaded', $uploadedByType === 'admin' ? null : null);
                
                $this->db->transComplete();
                return $fileId;
            }

            $this->db->transRollback();
            return false;

        } catch (\Exception $e) {
            $this->db->transRollback();
            log_message('error', 'File upload error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Approve file
     */
    public function approveFile($fileId, $validatedBy, $notes = null)
    {
        $updateData = [
            'validation_status' => 'approved',
            'validated_by' => $validatedBy,
            'validated_at' => date('Y-m-d H:i:s'),
            'validation_notes' => $notes
        ];

        $result = $this->update($fileId, $updateData);

        if ($result) {
            $file = $this->find($fileId);
            $this->updateBookingDocumentStatus($file['booking_id']);
            $this->logFileAudit($fileId, 'approved', $validatedBy, $notes);
        }

        return $result;
    }

    /**
     * Reject file
     */
    public function rejectFile($fileId, $validatedBy, $notes)
    {
        $updateData = [
            'validation_status' => 'rejected',
            'validated_by' => $validatedBy,
            'validated_at' => date('Y-m-d H:i:s'),
            'validation_notes' => $notes
        ];

        $result = $this->update($fileId, $updateData);

        if ($result) {
            $file = $this->find($fileId);
            $this->updateBookingDocumentStatus($file['booking_id']);
            $this->logFileAudit($fileId, 'rejected', $validatedBy, $notes);
        }

        return $result;
    }

    /**
     * Update booking document status flags
     */
    private function updateBookingDocumentStatus($bookingId)
    {
        $bookingModel = new \App\Models\BookingModel();
        
        $allUploaded = $this->areAllFilesUploaded($bookingId);
        $allApproved = $this->areAllFilesApproved($bookingId);

        $bookingModel->update($bookingId, [
            'documents_uploaded' => $allUploaded,
            'documents_validated' => $allUploaded, // At least all are uploaded
            'all_files_approved' => $allApproved
        ]);
    }

    /**
     * Log file audit trail
     */
    private function logFileAudit($fileId, $action, $performedBy = null, $notes = null, $oldValues = null, $newValues = null)
    {
        $auditData = [
            'file_id' => $fileId,
            'action' => $action,
            'performed_by' => $performedBy ?: session('user_id'),
            'performed_at' => date('Y-m-d H:i:s'),
            'notes' => $notes,
            'old_values' => $oldValues ? json_encode($oldValues) : null,
            'new_values' => $newValues ? json_encode($newValues) : null
        ];

        $this->db->table('booking_file_audit')->insert($auditData);
    }

    /**
     * Get files pending validation
     */
    public function getPendingValidationFiles($limit = null)
    {
        $builder = $this->select('booking_files.*, bookings.client_name, bookings.event_title, facilities.name as facility_name')
                       ->join('bookings', 'bookings.id = booking_files.booking_id')
                       ->join('facilities', 'facilities.id = bookings.facility_id')
                       ->where('booking_files.validation_status', 'pending')
                       ->orderBy('booking_files.uploaded_at', 'ASC');

        if ($limit) {
            $builder->limit($limit);
        }

        return $builder->findAll();
    }

    /**
     * Get file statistics
     */
    public function getFileStatistics()
    {
        $stats = [
            'total_files' => $this->countAll(),
            'pending_validation' => $this->where('validation_status', 'pending')->countAllResults(false),
            'approved_files' => $this->where('validation_status', 'approved')->countAllResults(false),
            'rejected_files' => $this->where('validation_status', 'rejected')->countAllResults(false)
        ];

        // Files by type
        $fileTypeStats = $this->select('file_type, COUNT(*) as count')
                             ->groupBy('file_type')
                             ->findAll();

        $stats['by_type'] = [];
        foreach ($fileTypeStats as $typeStat) {
            $stats['by_type'][$typeStat['file_type']] = $typeStat['count'];
        }

        return $stats;
    }

    /**
     * Delete file and its audit records
     */
    public function deleteFileCompletely($fileId)
    {
        $this->db->transStart();

        try {
            // Get file info first
            $file = $this->find($fileId);
            if (!$file) {
                return false;
            }

            // Delete physical file
            if (file_exists($file['file_path'])) {
                unlink($file['file_path']);
            }

            // Delete audit records
            $this->db->table('booking_file_audit')->where('file_id', $fileId)->delete();

            // Delete file record
            $result = $this->delete($fileId);

            if ($result) {
                // Update booking status
                $this->updateBookingDocumentStatus($file['booking_id']);
            }

            $this->db->transComplete();
            return $result;

        } catch (\Exception $e) {
            $this->db->transRollback();
            log_message('error', 'File deletion error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get required file types based on organization type
     */
    public function getRequiredFileTypes($organizationType = 'external')
    {
        $baseTypes = ['moa_signed', 'billing_confirmed'];

        if ($organizationType === 'external') {
            return array_merge($baseTypes, ['equipment_signed', 'payment_receipt']);
        } else {
            // Internal organizations might have reduced requirements
            return array_merge($baseTypes, ['equipment_signed']);
        }
    }
}