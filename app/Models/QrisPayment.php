<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $transaction_id
 * @property string $partner_reference_no
 * @property string $external_id
 * @property string|null $reference_no
 * @property string $qr_content
 * @property int $amount
 * @property Carbon $expires_at
 * @property string $status
 * @property Carbon|null $paid_at
 * @property array<string, mixed>|null $notify_payload
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Transaction $transaction
 */
#[Fillable([
    'transaction_id',
    'partner_reference_no',
    'external_id',
    'reference_no',
    'qr_content',
    'amount',
    'expires_at',
    'status',
    'paid_at',
    'notify_payload',
])]
class QrisPayment extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PAID = 'paid';

    public const STATUS_SUPERSEDED = 'superseded';

    /**
     * @return BelongsTo<Transaction, $this>
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'expires_at' => 'datetime',
            'paid_at' => 'datetime',
            'notify_payload' => 'array',
        ];
    }
}
