<?php

namespace App\Controllers;

use CodeIgniter\Controller;

class Contact extends Controller
{
    public function index()
    {
        // Check if user is logged in
        $session = session();
        $data = [
            'isLoggedIn' => $session->get('user_id') !== null,
            'userRole' => $session->get('role')
        ];
        
        return view('contact', $data);
    }

    public function sendMessage()
    {
        // Validate input
        $validation = \Config\Services::validation();
        
        $validation->setRules([
            'firstName' => 'required|min_length[2]|max_length[50]',
            'lastName' => 'required|min_length[2]|max_length[50]',
            'email' => 'required|valid_email',
            'phone' => 'permit_empty|min_length[10]|max_length[20]',
            'subject' => 'required',
            'message' => 'required|min_length[10]|max_length[1000]'
        ]);

        if (!$validation->withRequest($this->request)->run()) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Please check all required fields.',
                'errors' => $validation->getErrors()
            ]);
        }

        // Get form data
        $firstName = $this->request->getPost('firstName');
        $lastName = $this->request->getPost('lastName');
        $email = $this->request->getPost('email');
        $phone = $this->request->getPost('phone') ?? 'Not provided';
        $subject = $this->request->getPost('subject');
        $message = $this->request->getPost('message');

        // Prepare email
        $emailService = \Config\Services::email();
        
        // Email to admin (yourself)
        $emailService->setTo('cspcsphere@gmail.com'); // Your admin email
        $emailService->setFrom('cspcsphere@gmail.com', $firstName . ' ' . $lastName);
        $emailService->setReplyTo($email, $firstName . ' ' . $lastName); // User can reply directly
        $emailService->setSubject('Contact Form: ' . $this->getSubjectLabel($subject));
        
        $emailBody = $this->getEmailTemplate([
            'firstName' => $firstName,
            'lastName' => $lastName,
            'email' => $email,
            'phone' => $phone,
            'subject' => $this->getSubjectLabel($subject),
            'message' => nl2br(htmlspecialchars($message))
        ]);
        
        $emailService->setMessage($emailBody);

        // Send email
        if ($emailService->send()) {
            // Send confirmation email to user
            $this->sendConfirmationEmail($email, $firstName);
            
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Thank you for your message! We\'ll get back to you within 24 hours.'
            ]);
        } else {
            log_message('error', 'Email send failed: ' . $emailService->printDebugger(['headers']));
            
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Sorry, there was an error sending your message. Please try again later.'
            ]);
        }
    }

    private function sendConfirmationEmail($email, $firstName)
    {
        $emailService = \Config\Services::email();
        $emailService->clear();
        
        $emailService->setTo($email);
        $emailService->setFrom('cspcsphere@gmail.com', 'CSPC Booking System');
        $emailService->setSubject('Thank you for contacting CSPC');
        
        $confirmationBody = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <div style='background: linear-gradient(135deg, #1e3c72, #2a5298); padding: 30px; text-align: center;'>
                <h2 style='color: white; margin: 0;'>CSPC Booking System</h2>
            </div>
            <div style='padding: 30px; background: #f8fafc;'>
                <h3 style='color: #1e3c72;'>Thank You, {$firstName}!</h3>
                <p style='color: #64748b; line-height: 1.6;'>
                    We've received your message and will respond within 24 hours during business days.
                </p>
                <p style='color: #64748b; line-height: 1.6;'>
                    If you have an urgent matter, please call us at +63 (54) 123-4567.
                </p>
                <div style='margin-top: 30px; padding-top: 20px; border-top: 1px solid #e2e8f0;'>
                    <p style='color: #94a3b8; font-size: 14px; margin: 0;'>
                        Camarines Sur Polytechnic Colleges<br>
                        Nabua, Camarines Sur, Philippines
                    </p>
                </div>
            </div>
        </div>
        ";
        
        $emailService->setMessage($confirmationBody);
        $emailService->send();
    }

    private function getEmailTemplate($data)
    {
        return "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <div style='background: linear-gradient(135deg, #1e3c72, #2a5298); padding: 30px; text-align: center;'>
                <h2 style='color: white; margin: 0;'>New Contact Form Submission</h2>
            </div>
            <div style='padding: 30px; background: #ffffff;'>
                <table style='width: 100%; border-collapse: collapse;'>
                    <tr>
                        <td style='padding: 12px 0; border-bottom: 1px solid #e2e8f0; font-weight: 600; color: #1e3c72; width: 30%;'>Name:</td>
                        <td style='padding: 12px 0; border-bottom: 1px solid #e2e8f0; color: #64748b;'>{$data['firstName']} {$data['lastName']}</td>
                    </tr>
                    <tr>
                        <td style='padding: 12px 0; border-bottom: 1px solid #e2e8f0; font-weight: 600; color: #1e3c72;'>Email:</td>
                        <td style='padding: 12px 0; border-bottom: 1px solid #e2e8f0; color: #64748b;'><a href='mailto:{$data['email']}'>{$data['email']}</a></td>
                    </tr>
                    <tr>
                        <td style='padding: 12px 0; border-bottom: 1px solid #e2e8f0; font-weight: 600; color: #1e3c72;'>Phone:</td>
                        <td style='padding: 12px 0; border-bottom: 1px solid #e2e8f0; color: #64748b;'>{$data['phone']}</td>
                    </tr>
                    <tr>
                        <td style='padding: 12px 0; border-bottom: 1px solid #e2e8f0; font-weight: 600; color: #1e3c72;'>Subject:</td>
                        <td style='padding: 12px 0; border-bottom: 1px solid #e2e8f0; color: #64748b;'>{$data['subject']}</td>
                    </tr>
                </table>
                <div style='margin-top: 25px;'>
                    <h4 style='color: #1e3c72; margin-bottom: 10px;'>Message:</h4>
                    <div style='background: #f8fafc; padding: 20px; border-radius: 8px; color: #64748b; line-height: 1.6;'>
                        {$data['message']}
                    </div>
                </div>
            </div>
        </div>
        ";
    }

    private function getSubjectLabel($value)
    {
        $subjects = [
            'facility-booking' => 'Facility Booking Inquiry',
            'event-planning' => 'Event Planning',
            'technical-support' => 'Technical Support',
            'billing' => 'Billing & Payment',
            'general' => 'General Information',
            'feedback' => 'Feedback & Suggestions'
        ];

        return $subjects[$value] ?? 'General Inquiry';
    }
}