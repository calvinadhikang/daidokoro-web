<?php

namespace App\Support;

use App\Models\Customer;

class CustomerFormatter
{
    /**
     * @return array{
     *     id: int,
     *     name: string,
     *     phone: string,
     *     phone_display: string,
     *     phone_local: string,
     *     transactions_count: int
     * }
     */
    public static function format(Customer $customer): array
    {
        return [
            'id' => $customer->id,
            'name' => $customer->name,
            'phone' => $customer->phone,
            'phone_display' => $customer->phone_display,
            'phone_local' => $customer->phone_local,
            'transactions_count' => (int) ($customer->transactions_count ?? 0),
        ];
    }
}
