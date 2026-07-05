<?php

namespace App\Services;

use App\Models\Category;
use App\Models\MenuModel;
use Illuminate\Database\Eloquent\Collection;

class MenuCatalogService
{
    /**
     * @return Collection<int, MenuModel>
     */
    public function allForBrowse(): Collection
    {
        return MenuModel::query()
            ->with(['addonGroups.options', 'categories:id,name'])
            ->orderByDesc('is_available')
            ->orderByDesc('is_recommended')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, MenuModel>
     */
    public function availableForOrdering(): Collection
    {
        return MenuModel::query()
            ->with(['addonGroups.options', 'categories:id,name'])
            ->where('is_available', true)
            ->orderByDesc('is_recommended')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, Category>
     */
    public function categoriesForOrdering(): Collection
    {
        return Category::query()
            ->whereHas('menus', fn ($query) => $query->where('is_available', true))
            ->orderBy('name')
            ->get(['id', 'name']);
    }
}
