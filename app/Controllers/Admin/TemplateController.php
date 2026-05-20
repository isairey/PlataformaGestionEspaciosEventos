<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\BookingModel;

class TemplateController extends BaseController
{
    protected $bookingModel;

    public function __construct()
    {
        $this->bookingModel = new BookingModel();
    }

    /**
     * Download specific template for a booking
     */
    public function downloadTemplate($templateType, $bookingId)
    {
        try {
            // Verify booking exists
            $booking = $this->bookingModel->getBookingWithFullDetails($bookingId);
            
            if (!$booking) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Booking not found'
                ])->setStatusCode(404);
            }

            switch ($templateType) {
                case 'moa':
                    return $this->generateMOATemplate($booking);
                case 'billing':
                    return $this->generateBillingTemplate($booking);
                case 'equipment':
                    return $this->generateEquipmentTemplate($booking);
                default:
                    return $this->response->setJSON([
                        'success' => false,
                        'message' => 'Invalid template type'
                    ])->setStatusCode(400);
            }

        } catch (\Exception $e) {
            log_message('error', 'Template download error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Template generation failed'
            ])->setStatusCode(500);
        }
    }

    /**
     * Download all templates as ZIP
     */
    public function downloadAllTemplates($bookingId)
    {
        try {
            $booking = $this->bookingModel->getBookingWithFullDetails($bookingId);
            
            if (!$booking) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Booking not found'
                ])->setStatusCode(404);
            }

            // Create temporary directory for templates
            $tempDir = WRITEPATH . 'temp/templates_' . $bookingId . '_' . time() . '/';
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            // Generate all templates
            $templates = [
                'moa' => 'Memorandum_of_Agreement.pdf',
                'billing' => 'Billing_Statement.pdf',
                'equipment' => 'Equipment_Request_Form.pdf'
            ];

            $generatedFiles = [];
            foreach ($templates as $type => $filename) {
                $filePath = $tempDir . $filename;
                if ($this->generateTemplateFile($type, $booking, $filePath)) {
                    $generatedFiles[] = $filePath;
                }
            }

            if (empty($generatedFiles)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'No templates could be generated'
                ])->setStatusCode(500);
            }

            // Create ZIP file
            $zipPath = $tempDir . "Booking_{$bookingId}_Templates.zip";
            $zip = new \ZipArchive();
            
            if ($zip->open($zipPath, \ZipArchive::CREATE) !== TRUE) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Could not create ZIP file'
                ])->setStatusCode(500);
            }

            foreach ($generatedFiles as $file) {
                $zip->addFile($file, basename($file));
            }
            $zip->close();

            // Return ZIP file
            $response = $this->response
                ->setHeader('Content-Type', 'application/zip')
                ->setHeader('Content-Disposition', 'attachment; filename="Booking_' . $bookingId . '_Templates.zip"')
                ->setHeader('Content-Length', filesize($zipPath))
                ->setBody(file_get_contents($zipPath));

            // Clean up temporary files
            $this->cleanupTempFiles($tempDir);

            return $response;

        } catch (\Exception $e) {
            log_message('error', 'Batch template download error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Batch download failed'
            ])->setStatusCode(500);
        }
    }

    /**
     * Generate MOA template
     */
    private function generateMOATemplate($booking)
    {
        $html = $this->generateMOAHTML($booking);
        return $this->generatePDFFromHTML($html, "MOA_Booking_{$booking['id']}.pdf");
    }

    /**
     * Generate Billing template
     */
    private function generateBillingTemplate($booking)
    {
        $html = $this->generateBillingHTML($booking);
        return $this->generatePDFFromHTML($html, "Billing_Booking_{$booking['id']}.pdf");
    }

    /**
     * Generate Equipment template
     */
    private function generateEquipmentTemplate($booking)
    {
        $html = $this->generateEquipmentHTML($booking);
        return $this->generatePDFFromHTML($html, "Equipment_Booking_{$booking['id']}.pdf");
    }

    /**
     * Generate MOA HTML content
     */
    private function generateMOAHTML($booking)
    {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Memorandum of Agreement</title>
            <style>
                body { font-family: Arial, sans-serif; font-size: 12px; line-height: 1.4; }
                .header { text-align: center; margin-bottom: 30px; }
                .title { font-size: 16px; font-weight: bold; margin: 20px 0; }
                .section { margin: 15px 0; }
                .signature-area { margin-top: 50px; }
                .signature-box { border-bottom: 1px solid #000; width: 200px; display: inline-block; margin: 20px; }
                table { width: 100%; border-collapse: collapse; margin: 10px 0; }
                td, th { padding: 8px; text-align: left; }
                .form-field { background-color: #f0f0f0; padding: 5px; border: 1px solid #ccc; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1>CAMARINES SUR POLYTECHNIC COLLEGES</h1>
                <h2>MEMORANDUM OF AGREEMENT</h2>
                <p>Facility Rental Agreement</p>
            </div>

            <div class='section'>
                <strong>Booking Information:</strong>
                <table>
                    <tr><td>Booking ID:</td><td class='form-field'>BK" . str_pad($booking['id'], 3, '0', STR_PAD_LEFT) . "</td></tr>
                    <tr><td>Client Name:</td><td class='form-field'>{$booking['client_name']}</td></tr>
                    <tr><td>Organization:</td><td class='form-field'>{$booking['organization']}</td></tr>
                    <tr><td>Event Title:</td><td class='form-field'>{$booking['event_title']}</td></tr>
                    <tr><td>Facility:</td><td class='form-field'>{$booking['facility_name']}</td></tr>
                    <tr><td>Event Date:</td><td class='form-field'>" . date('F d, Y', strtotime($booking['event_date'])) . "</td></tr>
                    <tr><td>Event Time:</td><td class='form-field'>{$booking['event_time']}</td></tr>
                    <tr><td>Duration:</td><td class='form-field'>{$booking['duration']} hours</td></tr>
                    <tr><td>Attendees:</td><td class='form-field'>{$booking['attendees']} people</td></tr>
            </div>

            <div class='section'>
                <strong>Terms and Conditions:</strong>
                <ol>
                    <li>The CLIENT agrees to use the facility in accordance with CSPC policies and regulations.</li>
                    <li>The CLIENT is responsible for any damages incurred during the event.</li>
                    <li>Payment must be completed before the event date.</li>
                    <li>Cancellations must be made at least 48 hours in advance.</li>
                    <li>The CLIENT agrees to comply with all safety and security guidelines.</li>
                </ol>
            </div>

            <div class='section'>
                <strong>Financial Obligations:</strong>
                <table>
                    <tr><td>Total Amount Due:</td><td class='form-field'>₱" . number_format($booking['total_cost'], 2) . "</td></tr>
                    <tr><td>Payment Method:</td><td class='form-field'>_____________________</td></tr>
                    <tr><td>Payment Date:</td><td class='form-field'>_____________________</td></tr>
                </table>
            </div>

            <div class='signature-area'>
                <div style='display: flex; justify-content: space-between;'>
                    <div style='text-align: center;'>
                        <div class='signature-box'></div>
                        <p><strong>CLIENT SIGNATURE</strong><br>{$booking['client_name']}<br>Date: ___________</p>
                    </div>
                    <div style='text-align: center;'>
                        <div class='signature-box'></div>
                        <p><strong>CSPC REPRESENTATIVE</strong><br>Authorized Signatory<br>Date: ___________</p>
                    </div>
                </div>
            </div>
        </body>
        </html>";
    }

    /**
     * Generate Billing HTML content
     */
    private function generateBillingHTML($booking)
    {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Billing Statement</title>
            <style>
                body { font-family: Arial, sans-serif; font-size: 12px; line-height: 1.4; }
                .header { text-align: center; margin-bottom: 30px; }
                .title { font-size: 16px; font-weight: bold; margin: 20px 0; }
                .section { margin: 15px 0; }
                table { width: 100%; border-collapse: collapse; margin: 10px 0; }
                td, th { padding: 8px; border: 1px solid #ddd; text-align: left; }
                th { background-color: #f2f2f2; }
                .amount { text-align: right; }
                .total-row { background-color: #f9f9f9; font-weight: bold; }
                .form-field { background-color: #f0f0f0; padding: 5px; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1>CAMARINES SUR POLYTECHNIC COLLEGES</h1>
                <h2>BILLING STATEMENT</h2>
                <p>Facility Rental Services</p>
            </div>

            <div class='section'>
                <table>
                    <tr><td><strong>Bill To:</strong></td><td><strong>Billing Details:</strong></td></tr>
                    <tr>
                        <td class='form-field'>
                            {$booking['client_name']}<br>
                            {$booking['organization']}<br>
                            Email: {$booking['email_address']}<br>
                            Contact: {$booking['contact_number']}
                        </td>
                        <td class='form-field'>
                            Booking ID: BK" . str_pad($booking['id'], 3, '0', STR_PAD_LEFT) . "<br>
                            Date Issued: " . date('F d, Y') . "<br>
                            Due Date: " . date('F d, Y', strtotime($booking['event_date'] . ' -1 day')) . "<br>
                            Status: " . ucfirst($booking['status']) . "
                        </td>
                    </tr>
                </table>
            </div>

            <div class='section'>
                <strong>Service Details:</strong>
                <table>
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th>Quantity</th>
                            <th>Rate</th>
                            <th class='amount'>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>{$booking['facility_name']} - {$booking['plan_name']}</td>
                            <td>1</td>
                            <td>₱" . number_format($booking['total_cost'], 2) . "</td>
                            <td class='amount'>₱" . number_format($booking['total_cost'], 2) . "</td>
                        </tr>
                        <tr class='total-row'>
                            <td colspan='3'><strong>TOTAL AMOUNT DUE</strong></td>
                            <td class='amount'><strong>₱" . number_format($booking['total_cost'], 2) . "</strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class='section'>
                <strong>Payment Information:</strong>
                <p>Please submit payment confirmation along with this completed billing statement.</p>
                <table>
                    <tr><td>Payment Method Used:</td><td class='form-field'>_____________________</td></tr>
                    <tr><td>Reference Number:</td><td class='form-field'>_____________________</td></tr>
                    <tr><td>Date Paid:</td><td class='form-field'>_____________________</td></tr>
                    <tr><td>Amount Paid:</td><td class='form-field'>₱_____________________</td></tr>
                </table>
            </div>

            <div style='margin-top: 50px; text-align: center;'>
                <p><strong>Please return this completed form with proof of payment</strong></p>
            </div>
        </body>
        </html>";
    }

    /**
     * Generate Equipment HTML content
     */
    private function generateEquipmentHTML($booking)
    {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Equipment Request Form</title>
            <style>
                body { font-family: Arial, sans-serif; font-size: 12px; line-height: 1.4; }
                .header { text-align: center; margin-bottom: 30px; }
                .section { margin: 15px 0; }
                table { width: 100%; border-collapse: collapse; margin: 10px 0; }
                td, th { padding: 8px; border: 1px solid #ddd; text-align: left; }
                th { background-color: #f2f2f2; }
                .form-field { background-color: #f0f0f0; padding: 5px; }
                .checkbox { width: 20px; text-align: center; }
                .signature-area { margin-top: 40px; }
                .signature-box { border-bottom: 1px solid #000; width: 200px; display: inline-block; margin: 20px; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1>CAMARINES SUR POLYTECHNIC COLLEGES</h1>
                <h2>FACILITIES EQUIPMENT REQUEST FORM</h2>
            </div>

            <div class='section'>
                <strong>Event Information:</strong>
                <table>
                    <tr><td>Booking ID:</td><td class='form-field'>BK" . str_pad($booking['id'], 3, '0', STR_PAD_LEFT) . "</td></tr>
                    <tr><td>Event Title:</td><td class='form-field'>{$booking['event_title']}</td></tr>
                    <tr><td>Facility:</td><td class='form-field'>{$booking['facility_name']}</td></tr>
                    <tr><td>Date & Time:</td><td class='form-field'>" . date('F d, Y', strtotime($booking['event_date'])) . " at {$booking['event_time']}</td></tr>
                    <tr><td>Duration:</td><td class='form-field'>{$booking['duration']} hours</td></tr>
                    <tr><td>Expected Attendees:</td><td class='form-field'>{$booking['attendees']} people</td></tr>
                </table>
            </div>

            <div class='section'>
                <strong>Available Equipment (Check items needed):</strong>
                <table>
                    <thead>
                        <tr>
                            <th class='checkbox'>✓</th>
                            <th>Equipment</th>
                            <th>Quantity Available</th>
                            <th>Quantity Requested</th>
                            <th>Rate</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class='checkbox'>☐</td>
                            <td>Tables</td>
                            <td>95</td>
                            <td>_______</td>
                            <td>₱65.00 each</td>
                        </tr>
                        <tr>
                            <td class='checkbox'>☐</td>
                            <td>Monobloc Chairs</td>
                            <td>480</td>
                            <td>_______</td>
                            <td>₱7.50 each</td>
                        </tr>
                        <tr>
                            <td class='checkbox'>☐</td>
                            <td>Table Covers</td>
                            <td>95</td>
                            <td>_______</td>
                            <td>₱10.00 each</td>
                        </tr>
                        <tr>
                            <td class='checkbox'>☐</td>
                            <td>Chair Covers</td>
                            <td>190</td>
                            <td>_______</td>
                            <td>₱7.50 each</td>
                        </tr>
                        <tr>
                            <td class='checkbox'>☐</td>
                            <td>Lectern/Podium</td>
                            <td>5</td>
                            <td>_______</td>
                            <td>Included</td>
                        </tr>
                        <tr>
                            <td class='checkbox'>☐</td>
                            <td>Multimedia Projector</td>
                            <td>8</td>
                            <td>_______</td>
                            <td>Included</td>
                        </tr>
                        <tr>
                            <td class='checkbox'>☐</td>
                            <td>Microphone</td>
                            <td>18</td>
                            <td>_______</td>
                            <td>Included</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class='section'>
                <strong>Special Requirements:</strong>
                <div class='form-field' style='min-height: 60px; padding: 10px;'>
                    {$booking['special_requirements']}
                    <br><br>
                    Additional Notes: ________________________________
                    <br>
                    ________________________________________________
                    <br>
                    ________________________________________________
                </div>
            </div>

            <div class='signature-area'>
                <div style='display: flex; justify-content: space-between;'>
                    <div style='text-align: center;'>
                        <div class='signature-box'></div>
                        <p><strong>CLIENT SIGNATURE</strong><br>{$booking['client_name']}<br>Date: ___________</p>
                    </div>
                    <div style='text-align: center;'>
                        <div class='signature-box'></div>
                        <p><strong>CSPC STAFF SIGNATURE</strong><br>Equipment Manager<br>Date: ___________</p>
                    </div>
                </div>
                <p style='margin-top: 20px; text-align: center; font-size: 10px;'>
                    <strong>Note:</strong> This form must be completed and signed before equipment setup.
                </p>
            </div>
        </body>
        </html>";
    }

    /**
     * Generate PDF from HTML content
     */
    private function generatePDFFromHTML($html, $filename)
    {
        // You can use libraries like TCPDF, Dompdf, or mPDF
        // Here's a basic example using Dompdf (install via Composer: composer require dompdf/dompdf)
        
        /*
        // Uncomment and configure if using Dompdf
        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($dompdf->output());
        */
        
        // For now, return HTML as fallback (you should implement PDF generation)
        return $this->response
            ->setHeader('Content-Type', 'text/html')
            ->setHeader('Content-Disposition', 'attachment; filename="' . str_replace('.pdf', '.html', $filename) . '"')
            ->setBody($html);
    }

    /**
     * Generate template file and save to path
     */
    private function generateTemplateFile($type, $booking, $filePath)
    {
        try {
            $html = '';
            switch ($type) {
                case 'moa':
                    $html = $this->generateMOAHTML($booking);
                    break;
                case 'billing':
                    $html = $this->generateBillingHTML($booking);
                    break;
                case 'equipment':
                    $html = $this->generateEquipmentHTML($booking);
                    break;
                default:
                    return false;
            }

            // For this implementation, we'll save as HTML
            // In production, you would generate PDF files here
            file_put_contents($filePath, $html);
            return true;

        } catch (\Exception $e) {
            log_message('error', 'Template file generation error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Clean up temporary files
     */
    private function cleanupTempFiles($directory)
    {
        try {
            if (is_dir($directory)) {
                $files = array_diff(scandir($directory), array('.', '..'));
                foreach ($files as $file) {
                    $filePath = $directory . $file;
                    if (is_file($filePath)) {
                        unlink($filePath);
                    }
                }
                rmdir($directory);
            }
        } catch (\Exception $e) {
            log_message('error', 'Cleanup error: ' . $e->getMessage());
        }
    }
}