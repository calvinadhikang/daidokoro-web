<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property 'store'|'event' $type
 * @property string $name
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 * @property bool $closes_store
 * @property Carbon|null $archived_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'type',
    'name',
    'starts_at',
    'ends_at',
    'closes_store',
    'archived_at',
])]
class SalesChannel extends Model
{
    public const TYPE_STORE = 'store';

    public const TYPE_EVENT = 'event';

    public static function store(): self
    {
        return self::query()
            ->where('type', self::TYPE_STORE)
            ->firstOrFail();
    }

    public function isStore(): bool
    {
        return $this->type === self::TYPE_STORE;
    }

    public function isEvent(): bool
    {
        return $this->type === self::TYPE_EVENT;
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    /**
     * @param  Builder<SalesChannel>  $query
     * @return Builder<SalesChannel>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('archived_at');
    }

    /**
     * @param  Builder<SalesChannel>  $query
     * @return Builder<SalesChannel>
     */
    public function scopeEvents(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_EVENT);
    }

    /**
     * @return BelongsToMany<MenuModel, $this>
     */
    public function menus(): BelongsToMany
    {
        return $this->belongsToMany(MenuModel::class, 'channel_menu', 'sales_channel_id', 'menu_id')
            ->withPivot(['price_override', 'is_available'])
            ->withTimestamps();
    }

    /**
     * @return HasMany<Transaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * @return HasOne<OperatingClosure, $this>
     */
    public function closure(): HasOne
    {
        return $this->hasOne(OperatingClosure::class);
    }

    public function coversDate(string $date): bool
    {
        if ($this->isStore()) {
            return true;
        }

        if ($this->starts_at === null || $this->ends_at === null) {
            return false;
        }

        return $this->starts_at->toDateString() <= $date
            && $this->ends_at->toDateString() >= $date;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'date:Y-m-d',
            'ends_at' => 'date:Y-m-d',
            'closes_store' => 'boolean',
            'archived_at' => 'datetime',
        ];
    }
}
