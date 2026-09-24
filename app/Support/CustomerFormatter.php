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
     *     phone_country: string,
     *     phone_calling_code: string,
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
            ...PhoneNumber::metadata($customer->phone),
            'transactions_count' => (int) ($customer->transactions_count ?? 0),
        ];
    }
}
