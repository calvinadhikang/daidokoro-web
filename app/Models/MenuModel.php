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
 * @property bool $is_available
 * @property bool $is_recommended
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, MenuAddonGroup> $addonGroups
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Category> $categories
 */
#[Fillable(['name', 'image', 'price', 'is_available', 'is_recommended'])]
class MenuModel extends Model
{
    protected $table = 'menus';

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
