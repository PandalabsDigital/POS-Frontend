<?php

return [
    'countries' => [
        'IN' => ['name' => 'India', 'currency' => 'INR', 'authority' => 'GSTN / CBIC', 'system' => 'gst'],
        'AE' => ['name' => 'United Arab Emirates', 'currency' => 'AED', 'authority' => 'Federal Tax Authority (FTA)', 'system' => 'vat'],
        'SA' => ['name' => 'Saudi Arabia', 'currency' => 'SAR', 'authority' => 'ZATCA', 'system' => 'vat'],
        'BH' => ['name' => 'Bahrain', 'currency' => 'BHD', 'authority' => 'National Bureau for Revenue', 'system' => 'vat'],
        'OM' => ['name' => 'Oman', 'currency' => 'OMR', 'authority' => 'Oman Tax Authority', 'system' => 'vat'],
        'QA' => ['name' => 'Qatar', 'currency' => 'QAR', 'authority' => 'General Tax Authority', 'system' => 'none'],
        'KW' => ['name' => 'Kuwait', 'currency' => 'KWD', 'authority' => 'Ministry of Finance', 'system' => 'none'],
    ],

    'classifications' => [
        'standard' => 'Standard Rated',
        'reduced' => 'Reduced Rated',
        'zero_rated' => 'Zero Rated',
        'exempt' => 'Exempt',
        'out_of_scope' => 'Out of Scope',
        'not_applicable' => 'Tax Not Applicable',
    ],

    'payment_methods' => [
        'cash' => 'Cash',
        'card' => 'Card',
        'upi' => 'UPI',
        'online' => 'Online',
        'wallet' => 'Wallet',
        'other' => 'Other',
    ],
];
