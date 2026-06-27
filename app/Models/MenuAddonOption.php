<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $menu_addon_group_id
 * @property string $name
 * @property int $price
 * @property bool $is_available
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read MenuAddonGroup $group
 */
#[Fillable(['menu_addon_group_id', 'name', 'price', 'is_available', 'sort_order'])]
class MenuAddonOption extends Model
{
    /**
     * @return BelongsTo<MenuAddonGroup, $this>
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(MenuAddonGroup::class, 'menu_addon_group_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'is_available' => 'boolean',
        ];
    }
}
