<?php

namespace App\Providers;

use App\Models\Setting;
use App\Models\TaxRule;
use App\Models\TaxSetting;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();

        Blade::directive('money', function (string $expression): string {
            return "<?php echo e(money({$expression})); ?>";
        });

        View::composer('*', function ($view): void {
            try {
                date_default_timezone_set(Setting::timezone());
                $profile = Setting::restaurant();
                $address = trim($profile['address'].($profile['city'] !== '' ? ', '.$profile['city'] : ''), ' ,');
                $view->with('restaurantName', $profile['name']);
                $view->with('restaurantAddress', $address);
                $view->with('restaurantPhone', $profile['phone']);
                $view->with('receiptFooter', $profile['receipt_footer']);
                $view->with('showCashierOnReceipt', $profile['show_cashier_on_receipt']);
                $view->with('defaultOrderType', $profile['default_order_type']);
                $view->with('currency', Setting::currency());
                $view->with('restaurantLogoUrl', Setting::restaurantLogoUrl());
                $view->with('restAssuredLogoUrl', Setting::restAssuredLogoUrl());
                $view->with('restAssuredLockupUrl', Setting::restAssuredLockupUrl());
            } catch (Throwable) {
                $view->with('restaurantName', config('app.name'));
                $view->with('restaurantAddress', '');
                $view->with('restaurantPhone', '');
                $view->with('receiptFooter', 'Thank you');
                $view->with('showCashierOnReceipt', true);
                $view->with('defaultOrderType', 'dine_in');
                $view->with('currency', array_merge(config('currencies.USD'), ['code' => 'USD']));
                $view->with('restaurantLogoUrl', null);
                $view->with('restAssuredLogoUrl', asset('images/logo-icon.jpg'));
                $view->with('restAssuredLockupUrl', asset('images/logo.jpg'));
            }
        });

        View::composer(['categories._form', 'menu._form'], function ($view): void {
            try {
                $settings = TaxSetting::current()->load('defaultRule');
                $country = $settings->country_code;
                $view->with('taxRules', TaxRule::query()
                    ->with('components')
                    ->where('country_code', $country)
                    ->orderByDesc('is_default')
                    ->orderBy('tax_type')
                    ->get());
                $view->with('restaurantTaxRule', $settings->defaultRule);
            } catch (Throwable) {
                $view->with('taxRules', collect());
                $view->with('restaurantTaxRule', null);
            }
        });
    }
}
