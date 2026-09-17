<?php

namespace App\Services\Intelligence;

class ReportCatalog
{
    /**
     * @return array<string, array{title: string, hint: string, icon: string, group: string, source: string, purpose: string}>
     */
    public static function reports(): array
    {
        return [
            'daily' => ['title' => 'Today', 'hint' => 'Day totals', 'icon' => 'bi-calendar-check', 'group' => 'sales', 'source' => 'orders', 'purpose' => 'End-of-day totals'],
            'sales' => ['title' => 'Sales', 'hint' => 'Money in', 'icon' => 'bi-graph-up-arrow', 'group' => 'sales', 'source' => 'orders', 'purpose' => 'Sales over time'],
            'products' => ['title' => 'Products', 'hint' => 'What sold', 'icon' => 'bi-fork-knife', 'group' => 'menu', 'source' => 'orders+recipes', 'purpose' => 'Item sales'],
            'categories' => ['title' => 'Categories', 'hint' => 'Menu groups', 'icon' => 'bi-grid-fill', 'group' => 'menu', 'source' => 'orders', 'purpose' => 'Category mix'],
            'payments' => ['title' => 'Payments', 'hint' => 'Cash & cards', 'icon' => 'bi-credit-card-2-front', 'group' => 'sales', 'source' => 'orders', 'purpose' => 'Tenders'],
            'discounts' => ['title' => 'Discounts', 'hint' => 'Price cuts', 'icon' => 'bi-tag-fill', 'group' => 'sales', 'source' => 'orders', 'purpose' => 'Discounts'],
            'refunds' => ['title' => 'Refunds', 'hint' => 'Money back', 'icon' => 'bi-arrow-counterclockwise', 'group' => 'sales', 'source' => 'orders', 'purpose' => 'Refunds'],
            'shifts' => ['title' => 'Shifts', 'hint' => 'By day', 'icon' => 'bi-clock-history', 'group' => 'people', 'source' => 'orders', 'purpose' => 'Cashier days'],
            'employees' => ['title' => 'Staff', 'hint' => 'Cashiers', 'icon' => 'bi-person-badge', 'group' => 'people', 'source' => 'orders', 'purpose' => 'Staff sales'],
            'customers' => ['title' => 'Guests', 'hint' => 'Who came', 'icon' => 'bi-people-fill', 'group' => 'people', 'source' => 'orders', 'purpose' => 'Customers'],
            'tax' => ['title' => 'Tax', 'hint' => 'GST / VAT', 'icon' => 'bi-percent', 'group' => 'sales', 'source' => 'orders', 'purpose' => 'Tax collected'],
            'inventory' => ['title' => 'Stock', 'hint' => 'On hand', 'icon' => 'bi-box-seam-fill', 'group' => 'stock', 'source' => 'inventory', 'purpose' => 'Inventory'],
            'wastage' => ['title' => 'Waste', 'hint' => 'Thrown out', 'icon' => 'bi-trash-fill', 'group' => 'stock', 'source' => 'inventory', 'purpose' => 'Wastage'],
            'variance' => ['title' => 'Variance', 'hint' => 'Stock gaps', 'icon' => 'bi-sliders', 'group' => 'stock', 'source' => 'inventory', 'purpose' => 'Stock variance'],
            'purchases' => ['title' => 'Buys', 'hint' => 'Receiving', 'icon' => 'bi-bag-check-fill', 'group' => 'stock', 'source' => 'inventory', 'purpose' => 'Purchases'],
            'suppliers' => ['title' => 'Suppliers', 'hint' => 'Vendors', 'icon' => 'bi-truck', 'group' => 'stock', 'source' => 'inventory', 'purpose' => 'Suppliers'],
            'expenses' => ['title' => 'Expenses', 'hint' => 'Not in POS', 'icon' => 'bi-wallet2', 'group' => 'other', 'source' => 'unavailable', 'purpose' => 'Operating expenses'],
            'tables' => ['title' => 'Tables', 'hint' => 'Not in POS', 'icon' => 'bi-grid-3x3-gap', 'group' => 'other', 'source' => 'unavailable', 'purpose' => 'Table sales'],
            'delivery' => ['title' => 'Delivery', 'hint' => 'Channels', 'icon' => 'bi-bicycle', 'group' => 'sales', 'source' => 'orders', 'purpose' => 'Delivery vs POS'],
            'profitability' => ['title' => 'Profit', 'hint' => 'After cost', 'icon' => 'bi-piggy-bank-fill', 'group' => 'sales', 'source' => 'orders+inventory', 'purpose' => 'Profit'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function groups(): array
    {
        return [
            'sales' => 'Money',
            'menu' => 'Menu',
            'people' => 'People',
            'stock' => 'Stock',
            'other' => 'Other',
        ];
    }
}
