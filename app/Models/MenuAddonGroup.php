<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $menu_id
 * @property string $name
 * @property 'single'|'multiple' $selection_type
 * @property bool $is_required
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read MenuModel $menu
 * @property-read \Illuminate\Database\Eloquent\Collection<int, MenuAddonOption> $options
 */
#[Fillable(['menu_id', 'name', 'selection_type', 'is_required', 'sort_order'])]
class MenuAddonGroup extends Model
{
    /**
     * @return BelongsTo<MenuModel, $this>
     */
    public function menu(): BelongsTo
    {
        return $this->belongsTo(MenuModel::class, 'menu_id');
    }

    /**
     * @return HasMany<MenuAddonOption, $this>
     */
    public function options(): HasMany
    {
        return $this->hasMany(MenuAddonOption::class)->orderBy('sort_order');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
        ];
    }
}
