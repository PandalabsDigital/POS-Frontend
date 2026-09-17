<?php

namespace App\Http\Controllers;

use App\Enums\OrderType;
use App\Http\Requests\UpdateSettingRequest;
use App\Models\Setting;
use App\Services\InventorySettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function edit(): View
    {
        return view('settings.edit', [
            'settings' => Setting::restaurant(),
            'currencyCode' => Setting::currencyCode(),
            'currencies' => config('currencies'),
            'timezones' => config('restaurant.timezones'),
            'orderTypes' => OrderType::cases(),
            'inventory' => InventorySettings::current(),
        ]);
    }

    public function update(UpdateSettingRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['invoice_prefix'] = strtoupper((string) ($data['invoice_prefix'] ?: 'INV'));
        unset($data['restaurant_logo'], $data['remove_restaurant_logo']);

        if ($request->hasFile('restaurant_logo')) {
            $this->storeRestaurantLogo($request->file('restaurant_logo'));
        } elseif ($request->boolean('remove_restaurant_logo')) {
            $this->deleteRestaurantLogo();
        }

        foreach ($data as $key => $value) {
            if (! is_scalar($value) && $value !== null) {
                continue;
            }
            if (is_bool($value)) {
                Setting::put($key, $value ? '1' : '0');

                continue;
            }
            Setting::put($key, $value ?? '');
        }

        return redirect()->route('settings.edit')->with('success', 'Settings saved.');
    }

    private function storeRestaurantLogo(UploadedFile $file): void
    {
        $extension = $file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'png';
        $name = 'restaurant-logo-'.now()->format('YmdHis').'-'.bin2hex(random_bytes(3)).'.'.$extension;
        $path = $file->storeAs('branding', $name, 'public');

        if ($path === false) {
            throw ValidationException::withMessages([
                'restaurant_logo' => 'The logo could not be saved. Try a JPG or PNG under 2 MB.',
            ]);
        }

        $previous = (string) Setting::get('restaurant_logo', '');
        Setting::put('restaurant_logo', $path);

        if ($previous !== '' && $previous !== $path && Storage::disk('public')->exists($previous)) {
            Storage::disk('public')->delete($previous);
        }
    }

    private function deleteRestaurantLogo(): void
    {
        $path = (string) Setting::get('restaurant_logo', '');
        if ($path !== '' && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
        Setting::put('restaurant_logo', '');
    }
}
