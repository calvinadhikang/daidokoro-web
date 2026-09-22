<?php

namespace App\Support;

class PriceLabel
{
    public static function format(int $price, string $pricingType = 'standard'): string
    {
        $formatted = 'Rp '.number_format($price, 0, ',', '.');

        if ($pricingType === 'weight_based') {
            return $formatted.' / 100g';
        }

        return $formatted;
    }
}
