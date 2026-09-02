<?php

namespace App\Services;

use App\Models\Category;
use App\Models\MenuModel;
use Illuminate\Database\Eloquent\Builder;
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
        return $this->categoryFilterQuery()
            ->whereHas('menus', fn ($query) => $query->where('is_available', true))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * @return Collection<int, Category>
     */
    public function categoriesForBrowse(): Collection
    {
        return $this->categoryFilterQuery()
            ->whereHas('menus')
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * All assignable categories for filters and menu forms, excluding the
     * hardcoded Recommended label (that is the is_recommended flag).
     *
     * @return Collection<int, Category>
     */
    public function categoriesForFilters(): Collection
    {
        return $this->categoryFilterQuery()
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * @return Builder<Category>
     */
    private function categoryFilterQuery(): Builder
    {
        return Category::query()
            ->whereRaw('LOWER(name) <> ?', [strtolower(Category::HARDCODED_RECOMMENDED_NAME)]);
    }
}
