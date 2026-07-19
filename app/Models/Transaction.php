<?php

namespace App\Models;

use App\Services\TransactionNumberService;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $business_date
 * @property int $daily_number
 * @property string $customer_name
 * @property string $customer_phone
 * @property 'dine_in'|'takeaway' $service_type
 * @property string|null $table_code
 * @property 'in_progress'|'paid' $status
 * @property int $total_bill
 * @property bool $is_admin_created
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read string $transaction_number
 * @property-read \Illuminate\Database\Eloquent\Collection<int, TransactionItem> $items
 */
#[Fillable([
    'business_date',
    'daily_number',
    'customer_name',
    'customer_phone',
    'service_type',
    'table_code',
    'status',
    'total_bill',
    'is_admin_created',
])]
class Transaction extends Model
{
    /**
     * @var list<string>
     */
    protected $appends = ['transaction_number'];

    protected static function booted(): void
    {
        static::creating(function (Transaction $transaction): void {
            if ($transaction->business_date !== null && $transaction->daily_number !== null) {
                return;
            }

            $allocation = app(TransactionNumberService::class)->allocateNext();

            $transaction->business_date = $allocation['business_date'];
            $transaction->daily_number = $allocation['daily_number'];
        });
    }

    /**
     * @return HasMany<TransactionItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(TransactionItem::class)->latest();
    }

    public function recalculateTotal(): void
    {
        $this->update([
            'total_bill' => (int) $this->items()->sum('line_total'),
        ]);
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    /**
     * @return Attribute<string, never>
     */
    protected function transactionNumber(): Attribute
    {
        return Attribute::get(
            fn (): string => TransactionNumberService::format((int) $this->daily_number),
        );
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'business_date' => 'date',
            'daily_number' => 'integer',
            'total_bill' => 'integer',
            'is_admin_created' => 'boolean',
        ];
    }
}
