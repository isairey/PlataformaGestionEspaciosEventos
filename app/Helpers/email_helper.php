<?php

use CodeIgniter\Config\Services;

if (!function_exists('sendBookingNotification')) {
    /**
     * Send booking notification email
     * 
     * @param string $action 'approved', 'declined', or 'deleted'
     * @param array $booking Booking data
     * @param string|null $reason Optional reason for decline/delete
     * @return bool Success status
     */
    function sendBookingNotification($action, $booking, $reason = null)
    {
        $email = Services::email();
        
        try {
            $email->setTo($booking['email_address']);
            $email->setSubject(getEmailSubject($action, $booking));
            $email->setMessage(getEmailTemplate($action, $booking, $reason));
            
            $result = $email->send();
            
            if (!$result) {
                log_message('error', 'Email send failed: ' . $email->printDebugger(['headers']));
                return false;
            }
            
            log_message('info', "Booking notification sent: {$action} for booking #{$booking['id']} to {$booking['email_address']}");
            return true;
            
        } catch (\Exception $e) {
            log_message('error', 'Email exception: ' . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('getEmailSubject')) {
    function getEmailSubject($action, $booking)
    {
        $bookingId = 'BK' . str_pad($booking['id'], 3, '0', STR_PAD_LEFT);
        
        switch ($action) {
            case 'approved':
                return "✅ Booking Confirmed - {$bookingId}";
            case 'declined':
                return "❌ Booking Declined - {$bookingId}";
            case 'deleted':
                return "🗑️ Booking Deleted - {$bookingId}";
            case 'rescheduled':
                return "📅 Booking Rescheduled - {$bookingId}";
            default:
                return "Booking Update - {$bookingId}";
        }
    }
}

if (!function_exists('getEmailTemplate')) {
    function getEmailTemplate($action, $booking, $reason = null)
    {
        $bookingId = 'BK' . str_pad($booking['id'], 3, '0', STR_PAD_LEFT);
        $facilityName = $booking['facility_name'] ?? 'N/A';
        $eventDate = date('F d, Y', strtotime($booking['event_date']));
        $eventTime = $booking['event_time'];
        
        $styles = "
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
            .booking-details { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; }
            .detail-row { padding: 10px 0; border-bottom: 1px solid #eee; }
            .detail-label { font-weight: bold; color: #667eea; }
            .reason-box { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; border-radius: 4px; }
            .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
            .btn { display: inline-block; padding: 12px 24px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; margin: 10px 0; }
        ";
        
        switch ($action) {
            case 'approved':
                return getApprovedEmailTemplate($styles, $bookingId, $booking, $facilityName, $eventDate, $eventTime);
            case 'declined':
                return getDeclinedEmailTemplate($styles, $bookingId, $booking, $facilityName, $eventDate, $eventTime, $reason);
            case 'deleted':
                return getDeletedEmailTemplate($styles, $bookingId, $booking, $facilityName, $eventDate, $eventTime, $reason);
            case 'rescheduled':
                return getRescheduleEmailTemplate($styles, $bookingId, $booking, $facilityName, $eventDate, $eventTime, $reason);
            default:
                return '';
        }
    }
}

if (!function_exists('getApprovedEmailTemplate')) {
    function getApprovedEmailTemplate($styles, $bookingId, $booking, $facilityName, $eventDate, $eventTime)
    {
        $registrationLink = base_url("guest-registration/{$booking['id']}");
        $surveyLink = isset($booking['survey_token']) ? base_url("survey/{$booking['survey_token']}") : null;

        $surveySection = '';
        if ($surveyLink) {
            $surveySection = "
                    <div style='background: #fff3e0; border-left: 4px solid #ff9800; padding: 20px; margin: 20px 0; border-radius: 4px;'>
                        <h3 style='margin-top: 0; color: #ff9800;'>⭐ Facility Evaluation Survey</h3>
                        <p>After your event, we would appreciate your feedback on our facilities and services. Your input helps us improve continuously.</p>
                        <p>A survey link will be sent to you after the event. You can also access it anytime using the link below:</p>
                        <div style='text-align: center; margin: 20px 0;'>
                            <a href='{$surveyLink}' class='btn' style='font-size: 16px; background: #ff9800;'>Complete Facility Evaluation</a>
                        </div>
                        <p style='font-size: 13px; color: #666;'>
                            <strong>Survey Link:</strong><br>
                            <a href='{$surveyLink}'>{$surveyLink}</a>
                        </p>
                    </div>
            ";
        }

        return "
        <!DOCTYPE html>
        <html>
        <head>
            <style>{$styles}</style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>✅ Booking Confirmed!</h1>
                    <p>Your booking has been approved</p>
                </div>
                <div class='content'>
                    <p>Dear {$booking['client_name']},</p>
                    <p>Great news! Your facility booking request has been <strong>approved</strong>.</p>

                    <div class='booking-details'>
                        <h3>Booking Details</h3>
                        <div class='detail-row'>
                            <span class='detail-label'>Booking ID:</span> {$bookingId}
                        </div>
                        <div class='detail-row'>
                            <span class='detail-label'>Facility:</span> {$facilityName}
                        </div>
                        <div class='detail-row'>
                            <span class='detail-label'>Event Date:</span> {$eventDate}
                        </div>
                        <div class='detail-row'>
                            <span class='detail-label'>Event Time:</span> {$eventTime}
                        </div>
                        <div class='detail-row'>
                            <span class='detail-label'>Event Title:</span> {$booking['event_title']}
                        </div>
                    </div>

                    <div style='background: #e7f3ff; border-left: 4px solid #2196F3; padding: 20px; margin: 20px 0; border-radius: 4px;'>
                        <h3 style='margin-top: 0; color: #2196F3;'>📝 Guest Registration Required</h3>
                        <p><strong>Important:</strong> All guests attending your event must register in advance.</p>
                        <p>Share the registration link below with your guests:</p>
                        <div style='text-align: center; margin: 20px 0;'>
                            <a href='{$registrationLink}' class='btn' style='font-size: 16px;'>Register Guests Now</a>
                        </div>
                        <p style='font-size: 13px; color: #666;'>
                            <strong>Registration Link:</strong><br>
                            <a href='{$registrationLink}'>{$registrationLink}</a>
                        </p>
                        <p style='font-size: 13px; color: #666;'>
                            Each registered guest will receive a unique QR code via email for event check-in.
                        </p>
                    </div>

                    {$surveySection}

                    <p><strong>Next Steps:</strong></p>
                    <ul>
                        <li>Share the registration link with all event attendees</li>
                        <li>Ensure all guests register before the event date</li>
                        <li>Review all booking details carefully</li>
                        <li>Ensure compliance with facility guidelines</li>
                        <li>Contact us if you have any questions</li>
                    </ul>

                    <p>We look forward to hosting your event!</p>
                </div>
                <div class='footer'>
                    <p>CSPC Booking System<br>
                    This is an automated message. Please do not reply to this email.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
}

if (!function_exists('getDeclinedEmailTemplate')) {
    function getDeclinedEmailTemplate($styles, $bookingId, $booking, $facilityName, $eventDate, $eventTime, $reason)
    {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <style>{$styles}</style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>❌ Booking Declined</h1>
                    <p>Your booking request could not be approved</p>
                </div>
                <div class='content'>
                    <p>Dear {$booking['client_name']},</p>
                    <p>We regret to inform you that your facility booking request has been <strong>declined</strong>.</p>
                    
                    <div class='booking-details'>
                        <h3>Booking Details</h3>
                        <div class='detail-row'>
                            <span class='detail-label'>Booking ID:</span> {$bookingId}
                        </div>
                        <div class='detail-row'>
                            <span class='detail-label'>Facility:</span> {$facilityName}
                        </div>
                        <div class='detail-row'>
                            <span class='detail-label'>Event Date:</span> {$eventDate}
                        </div>
                        <div class='detail-row'>
                            <span class='detail-label'>Event Time:</span> {$eventTime}
                        </div>
                    </div>
                    
                    <div class='reason-box'>
                        <strong>📋 Reason for Decline:</strong><br>
                        {$reason}
                    </div>
                    
                    <p>If you have any questions or would like to discuss alternative arrangements, please contact us.</p>
                    
                    <p>Thank you for your understanding.</p>
                </div>
                <div class='footer'>
                    <p>CSPC Booking System<br>
                    This is an automated message. Please do not reply to this email.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
}

if (!function_exists('getDeletedEmailTemplate')) {
    function getDeletedEmailTemplate($styles, $bookingId, $booking, $facilityName, $eventDate, $eventTime, $reason)
    {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <style>{$styles}</style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🗑️ Booking Deleted</h1>
                    <p>Your booking has been removed from the system</p>
                </div>
                <div class='content'>
                    <p>Dear {$booking['client_name']},</p>
                    <p>This is to inform you that your booking has been <strong>deleted</strong> from our system.</p>
                    
                    <div class='booking-details'>
                        <h3>Deleted Booking Details</h3>
                        <div class='detail-row'>
                            <span class='detail-label'>Booking ID:</span> {$bookingId}
                        </div>
                        <div class='detail-row'>
                            <span class='detail-label'>Facility:</span> {$facilityName}
                        </div>
                        <div class='detail-row'>
                            <span class='detail-label'>Event Date:</span> {$eventDate}
                        </div>
                        <div class='detail-row'>
                            <span class='detail-label'>Event Time:</span> {$eventTime}
                        </div>
                    </div>
                    
                    " . ($reason ? "<div class='reason-box'>
                        <strong>📋 Reason:</strong><br>
                        {$reason}
                    </div>" : "") . "
                    
                    <p>If you believe this was done in error or have questions, please contact us immediately.</p>
                </div>
                <div class='footer'>
                    <p>CSPC Booking System<br>
                    This is an automated message. Please do not reply to this email.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
}

if (!function_exists('getRescheduleEmailTemplate')) {
    function getRescheduleEmailTemplate($styles, $bookingId, $booking, $facilityName, $eventDate, $eventTime, $reason = null)
    {
        $registrationLink = base_url("guest-registration/{$booking['id']}");
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <style>{$styles}</style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>📅 Booking Rescheduled</h1>
                    <p>Your event date has been updated</p>
                </div>
                <div class='content'>
                    <p>Dear {$booking['client_name']},</p>
                    <p>Your booking has been <strong>rescheduled</strong> to a new date. Please review the updated details below.</p>
                    
                    <div class='booking-details'>
                        <h3>Updated Booking Details</h3>
                        <div class='detail-row'>
                            <span class='detail-label'>Booking ID:</span> {$bookingId}
                        </div>
                        <div class='detail-row'>
                            <span class='detail-label'>Facility:</span> {$facilityName}
                        </div>
                        <div class='detail-row'>
                            <span class='detail-label'>Event Title:</span> {$booking['event_title']}
                        </div>
                        <div class='detail-row'>
                            <span class='detail-label'>📅 New Event Date:</span> <strong style='color: #28a745;'>{$eventDate}</strong>
                        </div>
                        <div class='detail-row'>
                            <span class='detail-label'>⏰ Event Time:</span> {$eventTime}
                        </div>
                    </div>
                    
                    " . ($reason ? "<div class='reason-box'>
                        <strong>📝 Reschedule Reason:</strong><br>
                        {$reason}
                    </div>" : "") . "
                    
                    <div style='background: #e8f5e9; border-left: 4px solid #4caf50; padding: 20px; margin: 20px 0; border-radius: 4px;'>
                        <h3 style='margin-top: 0; color: #4caf50;'>✅ Important Reminders</h3>
                        <ul style='margin: 10px 0;'>
                            <li>Please update any promotional materials with the new date</li>
                            <li>Notify all guests of the new event date</li>
                            <li>The guest registration link remains the same</li>
                            <li>All previous registrations are still valid</li>
                        </ul>
                    </div>
                    
                    <p><strong>Need to Update Guest Information?</strong></p>
                    <p>Share this link with guests if you need to add or update registrations:</p>
                    <div style='text-align: center; margin: 20px 0;'>
                        <a href='{$registrationLink}' class='btn' style='font-size: 16px;'>Manage Guest Registration</a>
                    </div>
                    <p style='font-size: 13px; color: #666;'>
                        <strong>Registration Link:</strong><br>
                        <a href='{$registrationLink}'>{$registrationLink}</a>
                    </p>
                    
                    <p>If you have any questions about this reschedule, please don't hesitate to contact us.</p>
                </div>
                <div class='footer'>
                    <p>CSPC Booking System<br>
                    This is an automated message. Please do not reply to this email.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
}

if (!function_exists('sendGuestRegistrationEmail')) {
    /**
     * Send guest registration confirmation with QR code
     *
     * @param array $guest Guest data including email, name, qr_code
     * @param array $booking Booking/event data
     * @param string $qrCodePath Path to QR code image file
     * @return bool Success status
     */
    function sendGuestRegistrationEmail($guest, $booking, $qrCodePath)
    {
        $email = Services::email();

        try {
            // Attach QR code image first and get CID
            $fullPath = WRITEPATH . $qrCodePath;
            $cid = null;

            log_message('info', "Email - Attempting to attach QR code: $fullPath");
            log_message('info', "Email - QR code path from DB: $qrCodePath");
            log_message('info', "Email - File exists: " . (file_exists($fullPath) ? 'YES' : 'NO'));

            if ($qrCodePath && file_exists($fullPath)) {
                $email->attach($fullPath);
                $cid = $email->setAttachmentCID($fullPath);
                log_message('info', "Email - QR code attached with CID: $cid");
            } else {
                log_message('error', "Email - QR code file NOT found for attachment: $fullPath");
            }

            // Set email headers and message with CID
            $email->setTo($guest['guest_email']);
            $email->setSubject("✅ Event Registration Confirmed - QR Code Inside");
            $email->setMessage(getGuestRegistrationEmailTemplate($guest, $booking, $cid));

            $result = $email->send();

            if (!$result) {
                log_message('error', 'Guest registration email send failed: ' . $email->printDebugger(['headers']));
                return false;
            }

            log_message('info', "Guest registration email sent to {$guest['guest_email']} for booking #{$booking['id']}");
            return true;

        } catch (\Exception $e) {
            log_message('error', 'Guest registration email exception: ' . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('getGuestRegistrationEmailTemplate')) {
    function getGuestRegistrationEmailTemplate($guest, $booking, $cid = null)
    {
        $eventDate = date('F d, Y', strtotime($booking['event_date']));
        $eventTime = $booking['event_time'];
        $facilityName = $booking['facility_name'] ?? 'N/A';

        $styles = "
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
            .booking-details { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; }
            .detail-row { padding: 10px 0; border-bottom: 1px solid #eee; }
            .detail-label { font-weight: bold; color: #667eea; }
            .qr-box { background: #e8f5e9; border: 2px solid #4caf50; padding: 20px; margin: 20px 0; border-radius: 8px; text-align: center; }
            .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
            .btn { display: inline-block; padding: 12px 24px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; margin: 10px 0; }
        ";

        return "
        <!DOCTYPE html>
        <html>
        <head>
            <style>{$styles}</style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🎉 Registration Confirmed!</h1>
                    <p>Your event QR code is ready</p>
                </div>
                <div class='content'>
                    <p>Dear {$guest['guest_name']},</p>
                    <p>Thank you for registering! Your registration has been confirmed for the following event:</p>

                    <div class='booking-details'>
                        <h3>Event Details</h3>
                        <div class='detail-row'>
                            <span class='detail-label'>Event Title:</span> {$booking['event_title']}
                        </div>
                        <div class='detail-row'>
                            <span class='detail-label'>Facility:</span> {$facilityName}
                        </div>
                        <div class='detail-row'>
                            <span class='detail-label'>Event Date:</span> {$eventDate}
                        </div>
                        <div class='detail-row'>
                            <span class='detail-label'>Event Time:</span> {$eventTime}
                        </div>
                    </div>

                    <div class='qr-box'>
                        <h3 style='margin-top: 0; color: #4caf50;'>📱 Your QR Code</h3>
                        <p><strong>QR Code ID:</strong> {$guest['qr_code']}</p>
                        " . ($cid ? "
                        <div style='text-align: center; margin: 20px 0;'>
                            <img src='cid:{$cid}' alt='QR Code' style='max-width: 300px; height: auto; border: 2px solid #4caf50; border-radius: 8px;'>
                        </div>
                        <p style='font-size: 14px; color: #666;'>
                            Your unique QR code is shown above. Please present it at the event entrance for check-in.
                        </p>
                        " : "
                        <p style='font-size: 14px; color: #666;'>
                            Your unique QR code is attached to this email. Please present it at the event entrance for check-in.
                        </p>
                        ") . "
                        <p style='font-size: 13px; color: #f44336; margin-top: 15px;'>
                            ⚠️ <strong>Important:</strong> Save this QR code! You can show it from your phone or print it.
                        </p>
                    </div>

                    <p><strong>What to bring:</strong></p>
                    <ul>
                        <li>This QR code (digital or printed)</li>
                        <li>Valid ID (if required)</li>
                        <li>Arrive 15 minutes early for smooth check-in</li>
                    </ul>

                    <p>If you have any questions, please contact the event organizer.</p>

                    <p>See you at the event!</p>
                </div>
                <div class='footer'>
                    <p>CSPC Booking System<br>
                    This is an automated message. Please do not reply to this email.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
}