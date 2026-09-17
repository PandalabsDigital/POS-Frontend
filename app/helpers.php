<?php

use App\Support\Money;

if (! function_exists('money')) {
    function money(float|int|string|null $amount): string
    {
        return Money::format($amount);
    }
}
