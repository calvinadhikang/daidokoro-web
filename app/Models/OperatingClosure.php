<?php

namespace App\Models;

use App\Services\StoreHoursService;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $starts_at
 * @property string $ends_at
 * @property string|null $label
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['starts_at', 'ends_at', 'label'])]
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

    public static function overlaps(string $startsAt, string $endsAt, ?int $exceptId = null): bool
    {
        return self::query()
            ->when($exceptId !== null, fn ($query) => $query->where('id', '!=', $exceptId))
            ->whereDate('starts_at', '<=', $endsAt)
            ->whereDate('ends_at', '>=', $startsAt)
            ->exists();
    }
}
