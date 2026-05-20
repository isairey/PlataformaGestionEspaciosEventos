<?php

namespace App\Services;

use CodeIgniter\Email\Email;

class CancellationEmailService
{
    protected $email;

    public function __construct()
    {
        $this->email = service('email');
    }

    /**
     * Send cancellation notification to system email with booking details
     */
    public function sendCancellationNotification($booking, $reason, $notes, $userEmail, $userFullName)
    {
        try {
            $systemEmail = 'cspcsphere@gmail.com';

            // Format booking details - using correct column names
            $bookingDate = date('F j, Y', strtotime($booking['event_date']));
            $bookingTime = date('g:i A', strtotime($booking['event_time']));
            $totalCost = '₱' . number_format($booking['total_cost'] ?? 0, 2);

            // Map reason to readable format
            $reasonMap = [
                'schedule-conflict' => 'Schedule Conflict',
                'facility-unavailable' => 'Facility Unavailable',
                'policy-violation' => 'Policy Violation',
                'incomplete-requirements' => 'Incomplete Requirements',
                'other' => 'Other'
            ];
            $reasonText = $reasonMap[$reason] ?? ucfirst($reason);

            // Build HTML email
            $htmlBody = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                    <div style='background-color: #f8f9fa; padding: 20px; border-radius: 5px; margin-bottom: 20px;'>
                        <h2 style='color: #dc3545; margin: 0; border-bottom: 3px solid #dc3545; padding-bottom: 10px;'>
                            <i style='color: #dc3545;'>●</i> Booking Cancellation Notice
                        </h2>
                    </div>

                    <div style='background-color: #fff; border-left: 4px solid #dc3545; padding: 15px; margin-bottom: 20px;'>
                        <p style='margin: 0; color: #666; font-size: 14px;'>
                            A user has requested a cancellation for their booking. The cancellation letter has been uploaded and is ready for review.
                        </p>
                    </div>

                    <h3 style='color: #333; border-bottom: 2px solid #ddd; padding-bottom: 10px; margin-bottom: 15px;'>
                        Cancellation Information
                    </h3>

                    <div style='background-color: #fff3cd; padding: 15px; border-radius: 5px; margin-bottom: 20px; border-left: 4px solid #ffc107;'>
                        <p style='margin: 10px 0;'><strong>Cancellation Reason:</strong></p>
                        <p style='margin: 5px 0; color: #333;'>{$reasonText}</p>

                        " . (!empty($notes) ? "
                        <p style='margin: 10px 0; margin-top: 15px;'><strong>Additional Notes:</strong></p>
                        <p style='margin: 5px 0; color: #333; line-height: 1.5;'>{$notes}</p>
                        " : "") . "
                    </div>

                    <h3 style='color: #333; border-bottom: 2px solid #ddd; padding-bottom: 10px; margin-bottom: 15px;'>
                        Booking Details
                    </h3>

                    <table style='width: 100%; border-collapse: collapse; margin-bottom: 20px;'>
                        <tr>
                            <td style='padding: 12px; background-color: #f8f9fa; border: 1px solid #ddd; font-weight: bold; width: 35%;'>Booking ID:</td>
                            <td style='padding: 12px; border: 1px solid #ddd;'>{$booking['id']}</td>
                        </tr>
                        <tr>
                            <td style='padding: 12px; background-color: #f8f9fa; border: 1px solid #ddd; font-weight: bold;'>User Email:</td>
                            <td style='padding: 12px; border: 1px solid #ddd;'>{$userEmail}</td>
                        </tr>
                        <tr>
                            <td style='padding: 12px; background-color: #f8f9fa; border: 1px solid #ddd; font-weight: bold;'>User Name:</td>
                            <td style='padding: 12px; border: 1px solid #ddd;'>{$userFullName}</td>
                        </tr>
                        <tr>
                            <td style='padding: 12px; background-color: #f8f9fa; border: 1px solid #ddd; font-weight: bold;'>Client Name:</td>
                            <td style='padding: 12px; border: 1px solid #ddd;'>{$booking['client_name']}</td>
                        </tr>
                        <tr>
                            <td style='padding: 12px; background-color: #f8f9fa; border: 1px solid #ddd; font-weight: bold;'>Contact Number:</td>
                            <td style='padding: 12px; border: 1px solid #ddd;'>{$booking['contact_number']}</td>
                        </tr>
                        <tr>
                            <td style='padding: 12px; background-color: #f8f9fa; border: 1px solid #ddd; font-weight: bold;'>Organization:</td>
                            <td style='padding: 12px; border: 1px solid #ddd;'>{$booking['organization']}</td>
                        </tr>
                        <tr>
                            <td style='padding: 12px; background-color: #f8f9fa; border: 1px solid #ddd; font-weight: bold;'>Event Title:</td>
                            <td style='padding: 12px; border: 1px solid #ddd;'>{$booking['event_title']}</td>
                        </tr>
                        <tr>
                            <td style='padding: 12px; background-color: #f8f9fa; border: 1px solid #ddd; font-weight: bold;'>Event Date:</td>
                            <td style='padding: 12px; border: 1px solid #ddd;'>{$bookingDate}</td>
                        </tr>
                        <tr>
                            <td style='padding: 12px; background-color: #f8f9fa; border: 1px solid #ddd; font-weight: bold;'>Event Time:</td>
                            <td style='padding: 12px; border: 1px solid #ddd;'>{$bookingTime}</td>
                        </tr>
                        <tr>
                            <td style='padding: 12px; background-color: #f8f9fa; border: 1px solid #ddd; font-weight: bold;'>Duration:</td>
                            <td style='padding: 12px; border: 1px solid #ddd;'>{$booking['duration']}</td>
                        </tr>
                        <tr>
                            <td style='padding: 12px; background-color: #f8f9fa; border: 1px solid #ddd; font-weight: bold;'>Total Cost:</td>
                            <td style='padding: 12px; border: 1px solid #ddd;'>{$totalCost}</td>
                        </tr>
                        <tr>
                            <td style='padding: 12px; background-color: #f8f9fa; border: 1px solid #ddd; font-weight: bold;'>Cancellation Requested:</td>
                            <td style='padding: 12px; border: 1px solid #ddd;'>" . date('F j, Y \a\t g:i A') . "</td>
                        </tr>
                    </table>

                    <div style='background-color: #e7f3ff; padding: 15px; border-radius: 5px; border-left: 4px solid #2196F3; margin-bottom: 20px;'>
                        <p style='margin: 0; color: #0c5460; font-size: 13px;'>
                            <strong>Action Required:</strong> Review the cancellation letter uploaded by the user in the administration panel.
                        </p>
                    </div>

                    <div style='text-align: center; color: #999; font-size: 12px; padding-top: 20px; border-top: 1px solid #ddd;'>
                        <p>This is an automated email from CSPC Booking System. Please do not reply to this email.</p>
                    </div>
                </div>
            ";

            $this->email->clear();
            $this->email->setFrom('cspcsphere@gmail.com', 'CSPC Booking System');
            $this->email->setTo($systemEmail);
            $this->email->setSubject('Booking Cancellation - Booking #' . $booking['id']);
            $this->email->setMessage($htmlBody);

            if ($this->email->send()) {
                log_message('info', "Cancellation notification email sent to {$systemEmail} for booking #{$booking['id']}");
                $this->email->clear();
                return true;
            } else {
                log_message('error', 'Failed to send cancellation notification email: ' . $this->email->printDebugger());
                return false;
            }
        } catch (\Exception $e) {
            log_message('error', 'Cancellation email service error: ' . $e->getMessage());
            return false;
        }
    }
}
