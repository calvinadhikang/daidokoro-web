<?php

namespace App\Services;

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
            ->with(['addonGroups.options'])
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
            ->with(['addonGroups.options'])
            ->where('is_available', true)
            ->orderByDesc('is_recommended')
            ->orderBy('name')
            ->get();
    }
}
