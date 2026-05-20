<?php

namespace App\Models;

use CodeIgniter\Model;

class ExtensionFileModel extends Model
{
    protected $table = 'extension_files';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';

    protected $allowedFields = [
        'booking_extension_id',
        'original_filename',
        'stored_filename',
        'file_path',
        'file_size',
        'mime_type',
        'uploaded_by',
        'upload_date',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    /**
     * Upload a file for extension
     */
    public function uploadFile($extensionId, $originalFilename, $storedFilename, $filePath, $fileSize, $mimeType, $uploadedById)
    {
        try {
            // Check if extension exists
            $extensionModel = new BookingExtensionModel();
            $extension = $extensionModel->find($extensionId);

            if (!$extension) {
                throw new \Exception('Extension not found');
            }

            $fileData = [
                'booking_extension_id' => $extensionId,
                'original_filename' => $originalFilename,
                'stored_filename' => $storedFilename,
                'file_path' => $filePath,
                'file_size' => $fileSize,
                'mime_type' => $mimeType,
                'uploaded_by' => $uploadedById,
                'upload_date' => date('Y-m-d H:i:s'),
            ];

            if ($this->insert($fileData)) {
                return [
                    'success' => true,
                    'file_id' => $this->getInsertID(),
                    'message' => 'File uploaded successfully',
                ];
            }

            throw new \Exception('Failed to save file record');
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get files for an extension
     */
    public function getExtensionFiles($extensionId)
    {
        return $this->where('booking_extension_id', $extensionId)
            ->orderBy('upload_date', 'DESC')
            ->findAll();
    }

    /**
     * Delete a file (hard delete)
     */
    public function deleteFile($fileId)
    {
        try {
            $file = $this->find($fileId);

            if (!$file) {
                throw new \Exception('File not found');
            }

            // Delete physical file
            if (file_exists($file['file_path'])) {
                unlink($file['file_path']);
            }

            // Delete database record
            $this->delete($fileId);

            return [
                'success' => true,
                'message' => 'File deleted successfully',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get payment order file for extension
     */
    public function getPaymentOrderFile($extensionId)
    {
        return $this->where('booking_extension_id', $extensionId)
            ->first();
    }

    /**
     * Get payment receipt files for extension
     */
    public function getPaymentReceiptFiles($extensionId)
    {
        return $this->where('booking_extension_id', $extensionId)
            ->findAll();
    }

    /**
     * Check if payment receipt exists
     */
    public function hasPaymentReceipt($extensionId)
    {
        return $this->where('booking_extension_id', $extensionId)
            ->countAllResults() > 0;
    }

    /**
     * Count files by type for extension
     */
    public function countFilesByType($extensionId)
    {
        return $this->where('booking_extension_id', $extensionId)
            ->countAllResults();
    }
}
