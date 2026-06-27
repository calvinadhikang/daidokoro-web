<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $transaction_id
 * @property int $menu_id
 * @property string $menu_name
 * @property int $quantity
 * @property int $unit_price
 * @property int $line_total
 * @property array<int, array<string, mixed>>|null $addons
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Transaction $transaction
 * @property-read MenuModel $menu
 */
#[Fillable([
    'transaction_id',
    'menu_id',
    'menu_name',
    'quantity',
    'unit_price',
    'line_total',
    'addons',
])]
class TransactionItem extends Model
{
    /**
     * @return BelongsTo<Transaction, $this>
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * @return BelongsTo<MenuModel, $this>
     */
    public function menu(): BelongsTo
    {
        return $this->belongsTo(MenuModel::class, 'menu_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'integer',
            'line_total' => 'integer',
            'addons' => 'array',
        ];
    }
}
