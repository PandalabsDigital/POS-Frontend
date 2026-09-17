<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

#[Fillable(['key', 'value'])]
class Setting extends Model
{
    public static function get(string $key, mixed $default = null): mixed
    {
        $settings = Cache::rememberForever('app_settings', function () {
            return static::query()->pluck('value', 'key')->all();
        });

        return $settings[$key] ?? $default;
    }

    public static function put(string $key, mixed $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget('app_settings');
    }

    public static function restaurantName(): string
    {
        return (string) static::get('restaurant_name', config('app.name', 'RestAssured'));
    }

    public static function timezone(): string
    {
        $timezone = (string) static::get('timezone', 'Asia/Kolkata');

        return array_key_exists($timezone, config('restaurant.timezones')) ? $timezone : 'Asia/Kolkata';
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $value = static::get($key);

        if ($value === null || $value === '') {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * @return array<string, mixed>
     */
    public static function restaurant(): array
    {
        return [
            'name' => static::restaurantName(),
            'address' => (string) static::get('address', ''),
            'city' => (string) static::get('city', ''),
            'phone' => (string) static::get('phone', ''),
            'email' => (string) static::get('email', ''),
            'website' => (string) static::get('website', ''),
            'opening_hours' => (string) static::get('opening_hours', ''),
            'receipt_footer' => (string) static::get('receipt_footer', 'Thank you'),
            'invoice_prefix' => strtoupper((string) static::get('invoice_prefix', 'INV')) ?: 'INV',
            'timezone' => static::timezone(),
            'default_order_type' => static::get('default_order_type', 'dine_in') ?: 'dine_in',
            'show_cashier_on_receipt' => static::bool('show_cashier_on_receipt', true),
            'logo_path' => (string) static::get('restaurant_logo', ''),
        ];
    }

    public static function restaurantLogoUrl(): ?string
    {
        $path = (string) static::get('restaurant_logo', '');

        if ($path === '' || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        $url = '/storage/'.ltrim(str_replace('\\', '/', $path), '/');

        try {
            return $url.'?v='.Storage::disk('public')->lastModified($path);
        } catch (\Throwable) {
            return $url;
        }
    }

    public static function restAssuredLogoUrl(): string
    {
        return asset('images/logo-icon.jpg');
    }

    public static function restAssuredLockupUrl(): string
    {
        return asset('images/logo.jpg');
    }

    public static function currencyCode(): string
    {
        $code = strtoupper((string) static::get('currency_code', 'USD'));

        return array_key_exists($code, config('currencies')) ? $code : 'USD';
    }

    /**
     * @return array{name: string, symbol: string, decimals: int, group: string, code: string}
     */
    public static function currency(): array
    {
        $code = static::currencyCode();
        $currency = config('currencies.'.$code);

        return array_merge($currency, ['code' => $code]);
    }
}
