<?php

return [
    'defaults' => [
        'enabled' => true,
        'require_dine_in' => false,
        'require_takeaway' => false,
        'require_delivery' => true,
        'allow_anonymous' => true,
        'require_skip_reason' => true,
        'require_other_note' => false,
        'minimum_info' => 'phone',
        'enable_analytics' => true,
        'terminal_name' => 'POS-1',
    ],

    'skip_reasons' => [
        'customer_declined' => [
            'label' => 'Customer declined',
            'status' => 'customer_declined',
        ],
        'customer_in_a_hurry' => [
            'label' => 'Customer was in a hurry',
            'status' => 'anonymous_order',
        ],
        'anonymous_order' => [
            'label' => 'Anonymous / takeaway order',
            'status' => 'anonymous_order',
        ],
        'staff_unable' => [
            'label' => 'Staff could not obtain details',
            'status' => 'staff_unable_to_capture',
        ],
        'other' => [
            'label' => 'Other',
            'status' => 'other',
        ],
    ],
];
