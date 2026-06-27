<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $customer_name
 * @property string $customer_phone
 * @property 'dine_in'|'takeaway' $service_type
 * @property 'in_progress'|'paid' $status
 * @property int $total_bill
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, TransactionItem> $items
 */
#[Fillable(['customer_name', 'customer_phone', 'service_type', 'status', 'total_bill'])]
class Transaction extends Model
{
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
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total_bill' => 'integer',
        ];
    }
}
