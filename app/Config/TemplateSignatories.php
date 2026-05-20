<?php

namespace App\Config;

/**
 * Template Signatories Configuration
 * 
 * This file documents how signatories are mapped to templates.
 * Each template has its signatory data stored in: writable/signatories/[TEMPLATE_NAME]_signatories.json
 * 
 * The system automatically:
 * - Loads saved signatories when displaying the File Templates page
 * - Allows editing signatories through a form interface
 * - Saves signatory data to JSON files in writable/signatories/ directory
 * - Tracks who saved the data and when
 * 
 * Template Mapping:
 * - Template files are located in: public/assets/templates/
 * - Signatory data is stored in: writable/signatories/
 * - Each template_name.ext has a corresponding template_name_signatories.json file
 * 
 * Example:
 *   Template File: public/assets/templates/Booking_Form.docx
 *   Signatories File: writable/signatories/Booking_Form_signatories.json
 * 
 * Signatory Data Structure:
 * {
 *     "template_name": "Booking_Form.docx",
 *     "signatory_1": "John Doe",
 *     "signatory_1_title": "Director",
 *     "signatory_2": "Jane Smith",
 *     "signatory_2_title": "Manager",
 *     "signatory_3": "",
 *     "signatory_3_title": "",
 *     "additional_info": "Any notes about this template",
 *     "saved_at": "2025-12-17 10:30:45",
 *     "saved_by": "Admin User"
 * }
 */

return [
    /**
     * Storage Settings
     */
    'signatory_storage_path' => FCPATH . 'writable/signatories/',
    'template_path' => FCPATH . 'public/assets/templates/',
    
    /**
     * Default template configuration
     * These are the available signatory fields for each template
     */
    'signatory_fields' => [
        'signatory_1' => [
            'label' => 'First Signatory Name',
            'required' => false,
        ],
        'signatory_1_title' => [
            'label' => 'First Signatory Title',
            'required' => false,
        ],
        'signatory_2' => [
            'label' => 'Second Signatory Name',
            'required' => false,
        ],
        'signatory_2_title' => [
            'label' => 'Second Signatory Title',
            'required' => false,
        ],
        'signatory_3' => [
            'label' => 'Third Signatory Name (Optional)',
            'required' => false,
        ],
        'signatory_3_title' => [
            'label' => 'Third Signatory Title',
            'required' => false,
        ],
        'additional_info' => [
            'label' => 'Additional Information',
            'required' => false,
        ],
    ],
];
