<?php

namespace App\Support;

use App\Models\Setting;

class Money
{
    public static function format(float|int|string|null $amount): string
    {
        $currency = Setting::currency();
        $decimals = (int) $currency['decimals'];
        $formatted = number_format((float) $amount, $decimals);

        return $currency['symbol'].$formatted;
    }
}
