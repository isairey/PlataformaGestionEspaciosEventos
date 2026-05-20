<?php

namespace App\Services;

use CodeIgniter\Email\Email;
use App\Models\UserModel;
use App\Models\BookingModel;

class ExtensionEmailService
{
    protected $email;
    protected $userModel;
    protected $bookingModel;

    public function __construct()
    {
        $this->email = service('email');
        $this->userModel = new UserModel();
        $this->bookingModel = new BookingModel();
    }

    public function sendExtensionRequestNotification($extension, $booking, $user)
    {
        try {
            $admins = $this->getAdminEmails();
            if (empty($admins)) {
                log_message('warning', 'No admin emails found for extension notification');
                return false;
            }

            $this->email->clear();
            $this->email->setFrom('cspcsphere@gmail.com', 'CSPC Booking System');
            $this->email->setTo($admins);
            $this->email->setSubject('New Extension Request - Booking #' . $booking['id']);
            $htmlBody = $this->buildExtensionRequestTemplate($extension, $booking, $user);
            $this->email->setMessage($htmlBody);

            if ($this->email->send()) {
                log_message('info', 'Extension request notification sent - Extension ID: ' . $extension['id']);
                $this->email->clear();
                return true;
            } else {
                log_message('error', 'Failed to send extension request notification: ' . $this->email->printDebugger());
                $this->email->clear();
                return false;
            }
        } catch (\Exception $e) {
            log_message('error', 'Error sending extension request notification: ' . $e->getMessage());
            $this->email->clear();
            return false;
        }
    }

    public function sendExtensionApprovalNotification($extension, $booking, $user, $paymentOrderPath = null)
    {
        try {
            // Check if user has email
            if (empty($user['email'])) {
                log_message('warning', 'User has no email address - Extension ID: ' . $extension['id']);
                return false;
            }

            $this->email->clear();
            $this->email->setFrom('cspcsphere@gmail.com', 'CSPC Booking System');
            $this->email->setTo($user['email']);
            $this->email->setSubject('Extension Approved - Booking #' . $booking['id']);
            $htmlBody = $this->buildExtensionApprovalTemplate($extension, $booking, $user);
            $this->email->setMessage($htmlBody);

            if ($paymentOrderPath && file_exists($paymentOrderPath)) {
                $this->email->attach($paymentOrderPath);
            }

            if ($this->email->send()) {
                log_message('info', 'Extension approval notification sent to ' . $user['email']);
                $this->email->clear();
                return true;
            } else {
                log_message('error', 'Failed to send extension approval notification: ' . $this->email->printDebugger());
                $this->email->clear();
                return false;
            }
        } catch (\Exception $e) {
            log_message('error', 'Error sending extension approval notification: ' . $e->getMessage());
            $this->email->clear();
            return false;
        }
    }

    public function sendExtensionRejectionNotification($extension, $booking, $user, $reason = '')
    {
        try {
            // Check if user has email
            if (empty($user['email'])) {
                log_message('warning', 'User has no email address - Extension ID: ' . $extension['id']);
                return false;
            }

            $this->email->clear();
            $this->email->setFrom('cspcsphere@gmail.com', 'CSPC Booking System');
            $this->email->setTo($user['email']);
            $this->email->setSubject('Extension Request Denied - Booking #' . $booking['id']);
            $htmlBody = $this->buildExtensionRejectionTemplate($extension, $booking, $user, $reason);
            $this->email->setMessage($htmlBody);

            if ($this->email->send()) {
                log_message('info', 'Extension rejection notification sent to ' . $user['email']);
                $this->email->clear();
                return true;
            } else {
                log_message('error', 'Failed to send extension rejection notification: ' . $this->email->printDebugger());
                $this->email->clear();
                return false;
            }
        } catch (\Exception $e) {
            log_message('error', 'Error sending extension rejection notification: ' . $e->getMessage());
            $this->email->clear();
            return false;
        }
    }

    public function sendPaymentConfirmationNotification($extension, $booking, $user)
    {
        try {
            // Check if user has email
            if (empty($user['email'])) {
                log_message('warning', 'User has no email address - Extension ID: ' . $extension['id']);
                return false;
            }

            $this->email->clear();
            $this->email->setFrom('cspcsphere@gmail.com', 'CSPC Booking System');
            $this->email->setTo($user['email']);
            $this->email->setSubject('Extension Payment Received - Booking #' . $booking['id']);
            $htmlBody = $this->buildPaymentConfirmationTemplate($extension, $booking, $user);
            $this->email->setMessage($htmlBody);

            if ($this->email->send()) {
                log_message('info', 'Payment confirmation notification sent to ' . $user['email']);
                $this->email->clear();
                return true;
            } else {
                log_message('error', 'Failed to send payment confirmation notification: ' . $this->email->printDebugger());
                $this->email->clear();
                return false;
            }
        } catch (\Exception $e) {
            log_message('error', 'Error sending payment confirmation notification: ' . $e->getMessage());
            $this->email->clear();
            return false;
        }
    }

    private function buildExtensionRequestTemplate($extension, $booking, $user)
    {
        $eventDate = date('F j, Y', strtotime($booking['event_date']));
        $requestedDate = date('F j, Y H:i A', strtotime($extension['created_at']));
        $costFormatted = number_format($extension['extension_cost'], 2);
        $bookingId = (int)$booking['id'];
        $facilityName = htmlspecialchars($booking['facility_name']);
        $fullName = htmlspecialchars($user['full_name']);
        $email = htmlspecialchars($user['email']);
        $extHours = (int)$extension['extension_hours'];
        $reason = htmlspecialchars($extension['extension_reason'] ?? 'No reason provided');

        return '<html><head><style>body{font-family:Arial,sans-serif;line-height:1.6;color:#333}.container{max-width:600px;margin:0 auto;padding:20px}.header{background-color:#0d6efd;color:white;padding:20px;border-radius:5px;margin-bottom:20px}.section{margin-bottom:20px;padding:15px;border:1px solid #ddd;border-radius:5px}.label{font-weight:bold;color:#0d6efd}.footer{margin-top:30px;padding-top:20px;border-top:1px solid #ddd;font-size:12px;color:#666}</style></head><body><div class="container"><div class="header"><h2>New Extension Request</h2></div><div class="section"><h3>Request Details</h3><p><span class="label">Booking ID:</span> #' . $bookingId . '</p><p><span class="label">Requested Hours:</span> ' . $extHours . ' hours</p><p><span class="label">Amount:</span> P' . $costFormatted . '</p><p><span class="label">Requested Date:</span> ' . $requestedDate . '</p></div><div class="section"><h3>Booking Information</h3><p><span class="label">Facility:</span> ' . $facilityName . '</p><p><span class="label">Student/Faculty:</span> ' . $fullName . ' (' . $email . ')</p><p><span class="label">Original Event Date:</span> ' . $eventDate . '</p><p><span class="label">Reason:</span> ' . $reason . '</p></div><div class="section"><h3>Action Required</h3><p>Please review this extension request in the admin panel.</p></div><div class="footer"><p>This is an automated notification from CSPC Booking System.</p></div></div></body></html>';
    }

    private function buildExtensionApprovalTemplate($extension, $booking, $user)
    {
        $eventDate = date('F j, Y', strtotime($booking['event_date']));
        $costFormatted = number_format($extension['extension_cost'], 2);
        $bookingId = (int)$booking['id'];
        $facilityName = htmlspecialchars($booking['facility_name']);
        $extHours = (int)$extension['extension_hours'];

        return '<html><head><style>body{font-family:Arial,sans-serif;line-height:1.6;color:#333}.container{max-width:600px;margin:0 auto;padding:20px}.header{background-color:#198754;color:white;padding:20px;border-radius:5px;margin-bottom:20px;text-align:center}.section{margin-bottom:20px;padding:15px;border:1px solid #ddd;border-radius:5px}.label{font-weight:bold;color:#198754}.note-box{background-color:#e7f5f1;border-left:4px solid #198754;padding:15px;margin-top:15px}.footer{margin-top:30px;padding-top:20px;border-top:1px solid #ddd;font-size:12px;color:#666}</style></head><body><div class="container"><div class="header"><h2>Extension Approved</h2></div><div class="section"><h3>Your extension request has been approved.</h3><p><span class="label">Booking ID:</span> #' . $bookingId . '</p><p><span class="label">Facility:</span> ' . $facilityName . '</p><p><span class="label">Extension Hours:</span> ' . $extHours . ' hours</p><p><span class="label">Amount Due:</span> P' . $costFormatted . '</p></div><div class="section"><h3>Next Steps</h3><ol><li>Review the attached payment order</li><li>Process payment according to instructions</li><li>Submit proof of payment to admin</li><li>Once verified, extension will be confirmed</li></ol></div><div class="note-box"><strong>Important:</strong> Keep the attached payment order for your records.</div><div class="section"><p><strong>Questions?</strong> Contact cspcsphere@gmail.com</p></div><div class="footer"><p>Automated notification from CSPC Booking System.</p></div></div></body></html>';
    }

    private function buildExtensionRejectionTemplate($extension, $booking, $user, $reason = '')
    {
        $eventDate = date('F j, Y', strtotime($booking['event_date']));
        $bookingId = (int)$booking['id'];
        $facilityName = htmlspecialchars($booking['facility_name']);
        $extHours = (int)$extension['extension_hours'];
        $reasonBox = !empty($reason) ? '<div style="background-color:#fff3cd;border-left:4px solid #ffc107;padding:15px;margin-top:15px"><strong>Reason:</strong><p>' . htmlspecialchars($reason) . '</p></div>' : '';

        return '<html><head><style>body{font-family:Arial,sans-serif;line-height:1.6;color:#333}.container{max-width:600px;margin:0 auto;padding:20px}.header{background-color:#dc3545;color:white;padding:20px;border-radius:5px;margin-bottom:20px;text-align:center}.section{margin-bottom:20px;padding:15px;border:1px solid #ddd;border-radius:5px}.label{font-weight:bold;color:#dc3545}.footer{margin-top:30px;padding-top:20px;border-top:1px solid #ddd;font-size:12px;color:#666}</style></head><body><div class="container"><div class="header"><h2>Extension Request Denied</h2></div><div class="section"><h3>Your extension request has been reviewed and denied.</h3><p><span class="label">Booking ID:</span> #' . $bookingId . '</p><p><span class="label">Facility:</span> ' . $facilityName . '</p><p><span class="label">Event Date:</span> ' . $eventDate . '</p><p><span class="label">Requested Hours:</span> ' . $extHours . ' hours</p></div>' . $reasonBox . '<div class="section"><h3>Next Steps</h3><p>If you believe this decision is in error, please contact the admin office.</p></div><div class="section"><p><strong>Questions?</strong> Contact cspcsphere@gmail.com</p></div><div class="footer"><p>Automated notification from CSPC Booking System.</p></div></div></body></html>';
    }

    private function buildPaymentConfirmationTemplate($extension, $booking, $user)
    {
        $eventDate = date('F j, Y', strtotime($booking['event_date']));
        $costFormatted = number_format($extension['extension_cost'], 2);
        $confirmedDate = date('F j, Y H:i A');
        $bookingId = (int)$booking['id'];
        $facilityName = htmlspecialchars($booking['facility_name']);
        $extHours = (int)$extension['extension_hours'];

        return '<html><head><style>body{font-family:Arial,sans-serif;line-height:1.6;color:#333}.container{max-width:600px;margin:0 auto;padding:20px}.header{background-color:#198754;color:white;padding:20px;border-radius:5px;margin-bottom:20px;text-align:center}.section{margin-bottom:20px;padding:15px;border:1px solid #ddd;border-radius:5px}.label{font-weight:bold;color:#198754}.confirmation-box{background-color:#e7f5f1;border-left:4px solid #198754;padding:15px;margin-top:15px;text-align:center}.footer{margin-top:30px;padding-top:20px;border-top:1px solid #ddd;font-size:12px;color:#666}</style></head><body><div class="container"><div class="header"><h2>Payment Confirmed</h2></div><div class="section"><h3>Your extension payment has been received and processed.</h3><p><span class="label">Booking ID:</span> #' . $bookingId . '</p><p><span class="label">Facility:</span> ' . $facilityName . '</p><p><span class="label">Event Date:</span> ' . $eventDate . '</p></div><div class="section"><h3>Extension Details</h3><p><span class="label">Extension Hours:</span> ' . $extHours . ' hours</p><p><span class="label">Amount Paid:</span> P' . $costFormatted . '</p><p><span class="label">Confirmation Date:</span> ' . $confirmedDate . '</p></div><div class="confirmation-box"><h4>Your booking extension is now ACTIVE</h4><p>You can use the extended facility hours as approved.</p></div><div class="section"><p>Questions? Contact the admin office.</p></div><div class="footer"><p>Automated notification from CSPC Booking System.</p></div></div></body></html>';
    }

    private function getAdminEmails()
    {
        $admins = $this->userModel
            ->whereIn('role', ['admin', 'facilitator'])
            ->select('email')
            ->findAll();

        return array_column($admins, 'email');
    }
}