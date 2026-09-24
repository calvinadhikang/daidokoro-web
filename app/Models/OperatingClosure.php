<?php

namespace App\Models;

use App\Services\StoreHoursService;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $sales_channel_id
 * @property Carbon $starts_at
 * @property Carbon $ends_at
 * @property string|null $label
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read SalesChannel|null $salesChannel
 */
#[Fillable(['sales_channel_id', 'starts_at', 'ends_at', 'label'])]
class OperatingClosure extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'date:Y-m-d',
            'ends_at' => 'date:Y-m-d',
        ];
    }

    /**
     * @param  Builder<OperatingClosure>  $query
     * @return Builder<OperatingClosure>
     */
    public function scopeEndingOnOrAfterToday(Builder $query): Builder
    {
        return $query->whereDate('ends_at', '>=', app(StoreHoursService::class)->today());
    }

    /**
     * @return BelongsTo<SalesChannel, $this>
     */
    public function salesChannel(): BelongsTo
    {
        return $this->belongsTo(SalesChannel::class);
    }

    public static function overlaps(string $startsAt, string $endsAt, ?int $exceptId = null): bool
    {
        return self::query()
            ->when($exceptId !== null, fn ($query) => $query->where('id', '!=', $exceptId))
            ->whereDate('starts_at', '<=', $endsAt)
            ->whereDate('ends_at', '>=', $startsAt)
            ->exists();
    }
}
