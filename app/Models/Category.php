<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, MenuModel> $menus
 */
#[Fillable(['name'])]
class Category extends Model
{
    public const HARDCODED_RECOMMENDED_NAME = 'Recommended';

    public static function isHardcodedRecommended(string $name): bool
    {
        return strcasecmp(trim($name), self::HARDCODED_RECOMMENDED_NAME) === 0;
    }

    /**
     * @return BelongsToMany<MenuModel, $this>
     */
    public function menus(): BelongsToMany
    {
        return $this->belongsToMany(MenuModel::class, 'category_menu', 'category_id', 'menu_id')
            ->orderBy('name');
    }
}
