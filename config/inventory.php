<?php

return [
    'defaults' => [
        'enabled' => true,
        'auto_deduct' => true,
        'allow_negative' => false,
        'auto_disable_menu' => false,
        'enable_batches' => true,
        'enable_expiry' => true,
        'low_stock_alerts' => true,
        'food_cost_target' => 30,
        'costing_method' => 'weighted_average',
        'expiry_warning_days' => 7,
    ],

    'adjustment_reasons' => [
        'physical_count' => 'Physical count',
        'damaged' => 'Damaged',
        'spoilage' => 'Spoilage',
        'theft' => 'Theft',
        'breakage' => 'Breakage',
        'data_correction' => 'Data correction',
        'opening_balance' => 'Opening balance',
        'other' => 'Other',
    ],

    'wastage_reasons' => [
        'spoiled' => 'Spoiled',
        'expired' => 'Expired',
        'burnt' => 'Burnt',
        'damaged' => 'Damaged',
        'overproduction' => 'Overproduction',
        'preparation_waste' => 'Preparation waste',
        'customer_return' => 'Customer return',
        'other' => 'Other',
    ],
];
