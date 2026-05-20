<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\I18n\Time;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;

class EventGuestModel extends Model
{
    protected $table = 'event_guests';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';

    protected $allowedFields = [
        'booking_id',
        'guest_name',
        'guest_email',
        'guest_phone',
        'qr_code',
        'qr_code_path',
        'attended',
        'attendance_time'
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = ''; // No updated_at column in database

    protected $validationRules = [
        'booking_id' => 'required|integer',
        'guest_name' => 'required|max_length[255]',
        'guest_email' => 'permit_empty|valid_email|max_length[255]',
        'guest_phone' => 'permit_empty|max_length[50]',
        'qr_code' => 'required|max_length[100]|is_unique[event_guests.qr_code]',
    ];

    protected $validationMessages = [
        'guest_name' => [
            'required' => 'Guest name is required'
        ],
        'guest_email' => [
            'valid_email' => 'Please provide a valid email address'
        ],
        'qr_code' => [
            'is_unique' => 'QR code already exists'
        ]
    ];

    /**
     * Get all guests for a specific booking
     */
    public function getGuestsByBooking($bookingId)
    {
        return $this->where('booking_id', $bookingId)
                    ->orderBy('created_at', 'DESC')
                    ->findAll();
    }

    /**
     * Get guest by QR code
     */
    public function getGuestByQRCode($qrCode)
    {
        return $this->where('qr_code', $qrCode)->first();
    }

    /**
     * Generate unique QR code string
     */
    public function generateUniqueQRCode()
    {
        do {
            // Generate random alphanumeric code (12 characters)
            $qrCode = strtoupper(bin2hex(random_bytes(6)));
        } while ($this->where('qr_code', $qrCode)->first());

        return $qrCode;
    }

    /**
     * Generate QR code image and save to file
     */
    public function generateQRCodeImage($qrCode, $guestName)
    {
        try {
            // Create QR code directory if it doesn't exist
            $qrDir = WRITEPATH . 'uploads/qr-codes/';

            log_message('info', "QR Generation - Directory: $qrDir");

            // Ensure directory exists with proper permissions
            if (!is_dir($qrDir)) {
                log_message('info', "Creating QR directory...");
                if (!mkdir($qrDir, 0755, true)) {
                    log_message('error', "Failed to create QR directory: $qrDir");
                    return null;
                }
                log_message('info', "QR directory created successfully");
            }

            // Make sure directory is writable
            if (!is_writable($qrDir)) {
                log_message('error', "QR directory is not writable: $qrDir");
                // Try to make it writable
                @chmod($qrDir, 0755);
            }

            log_message('info', "Directory exists: " . (is_dir($qrDir) ? 'YES' : 'NO'));
            log_message('info', "Directory writable: " . (is_writable($qrDir) ? 'YES' : 'NO'));

            // Generate filename
            $filename = $qrCode . '.png';
            $filepath = $qrDir . $filename;

            log_message('info', "QR file path: $filepath");

            // Build QR code using v6.0 API (named constructor parameters)
            log_message('info', "Building QR code for: $qrCode");
            $result = (new Builder(
                writer: new PngWriter(),
                data: $qrCode,
                encoding: new Encoding('UTF-8'),
                errorCorrectionLevel: ErrorCorrectionLevel::High,
                size: 300,
                margin: 10
            ))->build();

            // Save to file
            log_message('info', "Saving QR code to file...");
            $result->saveToFile($filepath);

            // Verify file was created
            if (file_exists($filepath)) {
                $filesize = filesize($filepath);
                log_message('info', "QR code file created successfully! Size: $filesize bytes");
                return 'uploads/qr-codes/' . $filename;
            } else {
                log_message('error', "QR code file was NOT created: $filepath");
                return null;
            }

            // Return relative path for database storage
            return 'uploads/qr-codes/' . $filename;
        } catch (\Exception $e) {
            log_message('error', 'QR Code generation failed: ' . $e->getMessage() . ' | File: ' . $e->getFile() . ' | Line: ' . $e->getLine());
            return null;
        }
    }

    /**
     * Create guest with auto-generated QR code
     */
    public function createGuest($data)
    {
        // Generate unique QR code
        $qrCode = $this->generateUniqueQRCode();
        $data['qr_code'] = $qrCode;

        // Generate QR code image
        $qrCodePath = $this->generateQRCodeImage($qrCode, $data['guest_name']);
        if ($qrCodePath) {
            $data['qr_code_path'] = $qrCodePath;
        }

        // Insert guest
        if ($this->insert($data)) {
            return [
                'success' => true,
                'id' => $this->getInsertID(),
                'qr_code' => $qrCode,
                'qr_code_path' => $qrCodePath
            ];
        }

        return ['success' => false, 'errors' => $this->errors()];
    }

    /**
     * Update guest attendance status
     */
    public function recordAttendance($qrCode)
    {
        $guest = $this->getGuestByQRCode($qrCode);

        if (!$guest) {
            return ['success' => false, 'message' => 'Guest not found'];
        }

        if ($guest['attended']) {
            return [
                'success' => false,
                'message' => 'Guest already checked in',
                'guest' => $guest
            ];
        }

        $updated = $this->update($guest['id'], [
            'attended' => 1,
            'attendance_time' => Time::now()->toDateTimeString()
        ]);

        if ($updated) {
            return [
                'success' => true,
                'message' => 'Guest checked in successfully',
                'guest' => $this->find($guest['id'])
            ];
        }

        return ['success' => false, 'message' => 'Failed to update attendance'];
    }

    /**
     * Manual check-in for guest
     */
    public function manualCheckIn($guestId, $notes = null)
    {
        $guest = $this->find($guestId);

        if (!$guest) {
            return ['success' => false, 'message' => 'Guest not found'];
        }

        if ($guest['attended']) {
            return [
                'success' => false,
                'message' => 'Guest already checked in',
                'guest' => $guest
            ];
        }

        $updated = $this->update($guestId, [
            'attended' => 1,
            'attendance_time' => Time::now()->toDateTimeString()
        ]);

        if ($updated) {
            return [
                'success' => true,
                'message' => 'Guest manually checked in',
                'guest' => $this->find($guestId)
            ];
        }

        return ['success' => false, 'message' => 'Failed to update attendance'];
    }

    /**
     * Get attendance statistics for a booking
     */
    public function getAttendanceStats($bookingId)
    {
        $guests = $this->getGuestsByBooking($bookingId);
        $total = count($guests);
        $attended = count(array_filter($guests, function($g) {
            return $g['attended'] == 1;
        }));
        $pending = $total - $attended;

        return [
            'total' => $total,
            'attended' => $attended,
            'pending' => $pending,
            'attendance_rate' => $total > 0 ? round(($attended / $total) * 100, 1) : 0
        ];
    }

    /**
     * Bulk create guests
     */
    public function createGuestsInBulk($guests)
    {
        $results = [];
        foreach ($guests as $guestData) {
            $result = $this->createGuest($guestData);
            $results[] = $result;
        }
        return $results;
    }

    /**
     * Delete guest and its QR code file
     */
    public function deleteGuest($guestId)
    {
        $guest = $this->find($guestId);

        if (!$guest) {
            return ['success' => false, 'message' => 'Guest not found'];
        }

        // Delete QR code file if exists
        if ($guest['qr_code_path']) {
            $filepath = WRITEPATH . $guest['qr_code_path'];
            if (file_exists($filepath)) {
                unlink($filepath);
            }
        }

        // Delete guest record
        if ($this->delete($guestId)) {
            return ['success' => true, 'message' => 'Guest deleted successfully'];
        }

        return ['success' => false, 'message' => 'Failed to delete guest'];
    }
}
