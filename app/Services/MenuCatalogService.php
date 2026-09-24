<?php

namespace App\Services;

use App\Models\Category;
use App\Models\MenuModel;
use App\Models\SalesChannel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class MenuCatalogService
{
    /**
     * @return Collection<int, MenuModel>
     */
    public function allForBrowse(?SalesChannel $channel = null): Collection
    {
        $channel ??= SalesChannel::store();

        return $this->channelQuery($channel)
            ->with(['addonGroups.options', 'categories:id,name'])
            ->orderByDesc('is_available')
            ->orderByDesc('is_recommended')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, MenuModel>
     */
    public function availableForOrdering(?SalesChannel $channel = null): Collection
    {
        $channel ??= SalesChannel::store();

        return $this->channelQuery($channel, availableOnly: true)
            ->with(['addonGroups.options', 'categories:id,name'])
            ->where('menus.is_available', true)
            ->orderByDesc('is_recommended')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, Category>
     */
    public function categoriesForOrdering(?SalesChannel $channel = null): Collection
    {
        $channel ??= SalesChannel::store();

        return $this->categoryFilterQuery()
            ->whereHas(
                'menus',
                fn ($query) => $this->constrainMenusToChannel($query, $channel, availableOnly: true)
                    ->where('menus.is_available', true),
            )
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * @return Collection<int, Category>
     */
    public function categoriesForBrowse(?SalesChannel $channel = null): Collection
    {
        $channel ??= SalesChannel::store();

        return $this->categoryFilterQuery()
            ->whereHas(
                'menus',
                fn ($query) => $this->constrainMenusToChannel($query, $channel),
            )
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
            ->withCount('menus')
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * @return Builder<MenuModel>
     */
    public function channelQuery(SalesChannel $channel, bool $availableOnly = false): Builder
    {
        return $this->constrainMenusToChannel(MenuModel::query(), $channel, $availableOnly)
            ->with([
                'salesChannels' => fn ($query) => $query->where('sales_channels.id', $channel->id),
            ]);
    }

    /**
     * @param  Builder<MenuModel>  $query
     * @return Builder<MenuModel>
     */
    private function constrainMenusToChannel(
        Builder $query,
        SalesChannel $channel,
        bool $availableOnly = false,
    ): Builder {
        return $query->whereHas('salesChannels', function ($channelQuery) use ($channel, $availableOnly) {
            $channelQuery->where('sales_channels.id', $channel->id);

            if ($availableOnly) {
                $channelQuery->where('channel_menu.is_available', true);
            }
        });
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
