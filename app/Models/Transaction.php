<?php

namespace App\Models;

use App\Services\TransactionNumberService;
use App\Support\PhoneNumber;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $sales_channel_id
 * @property Carbon $business_date
 * @property int $daily_number
 * @property string $customer_name
 * @property string $customer_phone
 * @property 'dine_in'|'takeaway' $service_type
 * @property string|null $table_code
 * @property 'in_progress'|'paid' $status
 * @property 'qris'|'cashier'|null $paid_via
 * @property int $total_bill
 * @property bool $is_admin_created
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read string $transaction_number
 * @property-read Collection<int, TransactionItem> $items
 * @property-read Collection<int, QrisPayment> $qrisPayments
 * @property-read SalesChannel $salesChannel
 */
#[Fillable([
    'sales_channel_id',
    'business_date',
    'daily_number',
    'customer_name',
    'customer_phone',
    'service_type',
    'table_code',
    'status',
    'paid_via',
    'total_bill',
    'is_admin_created',
])]
class Transaction extends Model
{
    public const PAID_VIA_QRIS = 'qris';

    public const PAID_VIA_CASHIER = 'cashier';

    /**
     * @var list<string>
     */
    protected $appends = [
        'transaction_number',
        'customer_phone_display',
        'customer_phone_country',
        'customer_phone_local',
    ];

    protected static function booted(): void
    {
        static::creating(function (Transaction $transaction): void {
            if ($transaction->sales_channel_id === null) {
                $transaction->sales_channel_id = SalesChannel::store()->id;
            }

            if ($transaction->business_date !== null && $transaction->daily_number !== null) {
                return;
            }

            $allocation = app(TransactionNumberService::class)->allocateNext(
                salesChannelId: (int) $transaction->sales_channel_id,
            );

            $transaction->business_date = $allocation['business_date'];
            $transaction->daily_number = $allocation['daily_number'];
        });
    }

    /**
     * @return BelongsTo<SalesChannel, $this>
     */
    public function salesChannel(): BelongsTo
    {
        return $this->belongsTo(SalesChannel::class);
    }

    /**
     * @return HasMany<TransactionItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(TransactionItem::class)->latest();
    }

    /**
     * @return HasMany<QrisPayment, $this>
     */
    public function qrisPayments(): HasMany
    {
        return $this->hasMany(QrisPayment::class);
    }

    public function markPaidByCashier(): void
    {
        $this->update([
            'status' => 'paid',
            'paid_via' => self::PAID_VIA_CASHIER,
        ]);
    }

    /**
     * @param  Builder<Transaction>  $query
     * @return Builder<Transaction>
     */
    public function scopeForPhone(Builder $query, string $phone): Builder
    {
        $values = PhoneNumber::matchingValues($phone);

        if ($values === []) {
            return $query->whereRaw('0 = 1');
        }

        return $query->whereIn('customer_phone', $values);
    }

    /**
     * @return Attribute<string, string|null>
     */
    protected function customerPhone(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => PhoneNumber::normalize($value) ?? $value,
        );
    }

    /**
     * @return Attribute<string, never>
     */
    protected function customerPhoneDisplay(): Attribute
    {
        return Attribute::get(
            fn (): string => PhoneNumber::formatForDisplay((string) $this->customer_phone),
        );
    }

    /**
     * @return Attribute<string, never>
     */
    protected function customerPhoneCountry(): Attribute
    {
        return Attribute::get(
            fn (): string => PhoneNumber::region((string) $this->customer_phone),
        );
    }

    /**
     * @return Attribute<string, never>
     */
    protected function customerPhoneLocal(): Attribute
    {
        return Attribute::get(
            fn (): string => PhoneNumber::toLocalInput((string) $this->customer_phone),
        );
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
