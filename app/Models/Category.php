<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, MenuModel> $menus
 */
#[Fillable(['name'])]
class Category extends Model
{
    /**
     * @return BelongsToMany<MenuModel, $this>
     */
    public function menus(): BelongsToMany
    {
        return $this->belongsToMany(MenuModel::class, 'category_menu', 'category_id', 'menu_id')
            ->orderBy('name');
    }
}
