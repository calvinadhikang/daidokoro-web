<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Transaction;
use App\Support\PhoneNumber;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class CustomerDirectoryService
{
    /**
     * @return Collection<int, Customer>
     */
    public function list(): Collection
    {
        $this->syncFromTransactions();

        $customers = Customer::query()
            ->orderBy('name')
            ->orderBy('id')
            ->get();

        $counts = Transaction::query()
            ->selectRaw('customer_phone, count(*) as aggregate')
            ->groupBy('customer_phone')
            ->pluck('aggregate', 'customer_phone');

        return $customers->each(function (Customer $customer) use ($counts): void {
            $customer->setAttribute(
                'transactions_count',
                collect(PhoneNumber::matchingValues($customer->phone))
                    ->sum(fn (string $phone) => (int) ($counts[$phone] ?? 0)),
            );
        });
    }

    /**
     * @return Collection<int, Transaction>
     */
    public function transactionsFor(Customer $customer): Collection
    {
        $phones = PhoneNumber::matchingValues($customer->phone);

        if ($phones === []) {
            return new Collection;
        }

        return Transaction::query()
            ->whereIn('customer_phone', $phones)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();
    }

    public function update(Customer $customer, string $name, string $phone): Customer
    {
        $oldPhones = PhoneNumber::matchingValues($customer->phone);
        $newPhone = PhoneNumber::normalize($phone) ?? $phone;

        return DB::transaction(function () use ($customer, $name, $newPhone, $oldPhones) {
            $customer->update([
                'name' => $name,
                'phone' => $newPhone,
            ]);

            if ($oldPhones !== []) {
                Transaction::query()
                    ->whereIn('customer_phone', $oldPhones)
                    ->update([
                        'customer_name' => $name,
                        'customer_phone' => $newPhone,
                    ]);
            }

            return $customer->fresh() ?? $customer;
        });
    }

    public function upsertFromNamePhone(string $name, string $phone): ?Customer
    {
        $normalized = PhoneNumber::normalize($phone);

        if ($normalized === null) {
            return null;
        }

        $trimmedName = trim($name);

        return Customer::query()->updateOrCreate(
            ['phone' => $normalized],
            ['name' => $trimmedName !== '' ? $trimmedName : 'Pelanggan'],
        );
    }

    public function syncFromTransactions(): void
    {
        $existing = Customer::query()
            ->pluck('phone')
            ->mapWithKeys(function (string $phone) {
                $normalized = PhoneNumber::normalize($phone) ?? $phone;

                return [$normalized => true];
            });

        $rows = Transaction::query()
            ->select('customer_phone', 'customer_name')
            ->whereNotNull('customer_phone')
            ->where('customer_phone', '!=', '')
            ->orderByDesc('id')
            ->get();

        foreach ($rows as $row) {
            $normalized = PhoneNumber::normalize($row->customer_phone);

            if ($normalized === null || $existing->has($normalized)) {
                continue;
            }

            $name = trim((string) $row->customer_name);

            Customer::query()->create([
                'name' => $name !== '' ? $name : 'Pelanggan',
                'phone' => $normalized,
            ]);

            $existing[$normalized] = true;
        }
    }
}
