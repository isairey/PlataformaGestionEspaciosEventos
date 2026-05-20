<?php

namespace Config;

class MoaSignatories
{
    /**
     * MOA Signatory Configuration
     * This defines the markers, labels, and formatting for each signatory field.
     * The actual names are stored in writable/signatories/ and can be changed via admin UI.
     */
    public const SIGNATORIES = [
        'vp_finance' => [
            'marker' => '###VP_FINANCE###',
            'label' => 'Vice President for Administration and Finance',
            'placeholder' => 'Enter name',
            'format' => ['bold' => true],
            'default_name' => 'NANCY S. PENETRANTE'
        ],
        'spmo_witness1' => [
            'marker' => '###SPMO_WITNESS1###',
            'label' => 'AOV/ SPMO III (Witness 1)',
            'placeholder' => 'Enter name',
            'format' => ['bold' => true],
            'default_name' => 'MARY CHIE A. DE LA CRUZ, CPA, MBA'
        ],
        'accountant' => [
            'marker' => '###ACCOUNTANT###',
            'label' => 'Accountant III (Witness 2)',
            'placeholder' => 'Enter name',
            'format' => ['bold' => true],
            'default_name' => 'KAREN H. CRUZATA, CPA'
        ]
    ];

    /**
     * Get all signatory markers for template processing
     */
    public static function getMarkers()
    {
        return array_map(fn($sig) => $sig['marker'], self::SIGNATORIES);
    }

    /**
     * Get signatory configuration for a specific key
     */
    public static function get($key)
    {
        return self::SIGNATORIES[$key] ?? null;
    }

    /**
     * Get all signatory fields for display
     */
    public static function getFields()
    {
        return array_map(function($key, $sig) {
            return [
                'key' => $key,
                'marker' => $sig['marker'],
                'label' => $sig['label'],
                'placeholder' => $sig['placeholder'],
                'format' => $sig['format'],
                'default_name' => $sig['default_name']
            ];
        }, array_keys(self::SIGNATORIES), self::SIGNATORIES);
    }
}
