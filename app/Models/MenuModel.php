<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string|null $image
 * @property int $price
 * @property string $pricing_type
 * @property bool $is_available
 * @property bool $is_recommended
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, MenuAddonGroup> $addonGroups
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Category> $categories
 * @property-read \Illuminate\Database\Eloquent\Collection<int, SalesChannel> $salesChannels
 */
#[Fillable(['name', 'image', 'price', 'pricing_type', 'is_available', 'is_recommended'])]
class MenuModel extends Model
{
    public const PRICING_STANDARD = 'standard';

    public const PRICING_WEIGHT_BASED = 'weight_based';

    /**
     * When set before save, the created hook assigns the menu to this channel
     * instead of the store catalog.
     */
    public ?int $assignToSalesChannelId = null;

    protected $table = 'menus';

    protected static function booted(): void
    {
        static::created(function (MenuModel $menu): void {
            $channelId = $menu->assignToSalesChannelId ?? SalesChannel::store()->id;

            $menu->salesChannels()->syncWithoutDetaching([
                $channelId => [
                    'price_override' => null,
                    'is_available' => $menu->is_available,
                ],
            ]);
        });
    }

    public function isWeightBased(): bool
    {
        return $this->pricing_type === self::PRICING_WEIGHT_BASED;
    }

    /**
     * @return BelongsToMany<SalesChannel, $this>
     */
    public function salesChannels(): BelongsToMany
    {
        return $this->belongsToMany(SalesChannel::class, 'channel_menu', 'menu_id', 'sales_channel_id')
            ->withPivot(['price_override', 'is_available'])
            ->withTimestamps();
    }

    public function effectivePrice(?SalesChannel $channel = null): int
    {
        $pivotPrice = $this->channelPivot($channel)?->price_override;

        return $pivotPrice !== null ? (int) $pivotPrice : (int) $this->price;
    }

    public function effectiveIsAvailable(?SalesChannel $channel = null): bool
    {
        if (! $this->is_available) {
            return false;
        }

        $pivot = $this->channelPivot($channel);

        if ($pivot === null) {
            return false;
        }

        return (bool) $pivot->is_available;
    }

    public function channelPivot(?SalesChannel $channel = null): ?object
    {
        if ($channel === null) {
            return $this->relationLoaded('salesChannels')
                ? $this->salesChannels->first()?->pivot
                : null;
        }

        if ($this->relationLoaded('salesChannels')) {
            return $this->salesChannels->firstWhere('id', $channel->id)?->pivot;
        }

        $assigned = $this->salesChannels()->where('sales_channels.id', $channel->id)->first();

        return $assigned?->pivot;
    }

    /**
     * @return HasMany<MenuAddonGroup, $this>
     */
    public function addonGroups(): HasMany
    {
        return $this->hasMany(MenuAddonGroup::class, 'menu_id')->orderBy('sort_order');
    }

    /**
     * @return BelongsToMany<Category, $this>
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_menu', 'menu_id', 'category_id')
            ->orderBy('name');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'is_available' => 'boolean',
            'is_recommended' => 'boolean',
        ];
    }
}
