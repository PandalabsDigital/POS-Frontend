<?php

namespace App\Services;

use App\Models\Setting;

class InventorySettings
{
    /**
     * @return array{
     *     enabled: bool,
     *     auto_deduct: bool,
     *     allow_negative: bool,
     *     auto_disable_menu: bool,
     *     enable_batches: bool,
     *     enable_expiry: bool,
     *     low_stock_alerts: bool,
     *     food_cost_target: float,
     *     costing_method: string,
     *     expiry_warning_days: int
     * }
     */
    public static function current(): array
    {
        $defaults = config('inventory.defaults');

        return [
            'enabled' => Setting::bool('inventory_enabled', (bool) $defaults['enabled']),
            'auto_deduct' => Setting::bool('inventory_auto_deduct', (bool) $defaults['auto_deduct']),
            'allow_negative' => Setting::bool('inventory_allow_negative', (bool) $defaults['allow_negative']),
            'auto_disable_menu' => Setting::bool('inventory_auto_disable_menu', (bool) $defaults['auto_disable_menu']),
            'enable_batches' => Setting::bool('inventory_enable_batches', (bool) $defaults['enable_batches']),
            'enable_expiry' => Setting::bool('inventory_enable_expiry', (bool) $defaults['enable_expiry']),
            'low_stock_alerts' => Setting::bool('inventory_low_stock_alerts', (bool) $defaults['low_stock_alerts']),
            'food_cost_target' => (float) Setting::get('inventory_food_cost_target', $defaults['food_cost_target']),
            'costing_method' => (string) Setting::get('inventory_costing_method', $defaults['costing_method']),
            'expiry_warning_days' => (int) Setting::get('inventory_expiry_warning_days', $defaults['expiry_warning_days']),
        ];
    }
}
