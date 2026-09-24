<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ManagesMenuImages;
use App\Http\Requests\StoreMenuRequest;
use App\Http\Requests\UpdateMenuRequest;
use App\Models\MenuAddonGroup;
use App\Models\MenuAddonOption;
use App\Models\MenuModel;
use App\Models\SalesChannel;
use App\Services\MenuCatalogService;
use App\Services\MenuImageService;
use App\Services\SalesChannelService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class MenuController extends Controller
{
    use ManagesMenuImages;

    public function __construct(
        private MenuImageService $menuImages,
        private MenuCatalogService $menuCatalog,
        private SalesChannelService $salesChannels,
    ) {}

    public function index(): Response
    {
        $menus = MenuModel::query()
            ->with(['addonGroups.options', 'categories:id,name'])
            ->orderByDesc('is_available')
            ->orderBy('name')
            ->get();

        return Inertia::render('admin/menus/index', [
            'menus' => $menus,
            'categories' => $this->menuCatalog->categoriesForBrowse(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/menus/create', [
            'channels' => $this->channelOptions(),
            'defaultChannelIds' => [SalesChannel::store()->id],
        ]);
    }

    public function store(StoreMenuRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated, $request) {
            $menu = MenuModel::query()->create([
                'name' => $validated['name'],
                'image' => $this->resolveMenuImageUrl($request, $this->menuImages),
                'price' => $validated['price'],
                'pricing_type' => $validated['pricing_type'] ?? MenuModel::PRICING_STANDARD,
                'is_available' => $validated['is_available'] ?? true,
                'is_recommended' => $validated['is_recommended'] ?? false,
            ]);

            $this->syncAddonGroups($menu, $validated['addon_groups'] ?? []);
            $this->salesChannels->syncMenuChannels(
                $menu,
                $validated['sales_channel_ids'] ?? [SalesChannel::store()->id],
            );
        });

        return redirect()
            ->route('admin.menus.index')
            ->with('success', 'Menu created successfully.');
    }

    public function show(MenuModel $menuModel): Response
    {
        $menuModel->load(['addonGroups.options', 'salesChannels']);

        return Inertia::render('admin/menus/show', [
            'menu' => $menuModel,
            'channels' => $this->channelOptions(),
            'assignedChannelIds' => $menuModel->salesChannels->pluck('id')->all(),
        ]);
    }

    public function update(UpdateMenuRequest $request, MenuModel $menuModel): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($menuModel, $validated, $request) {
            $menuModel->update([
                'name' => $validated['name'],
                'image' => $this->resolveMenuImageUrl($request, $this->menuImages, $menuModel->image),
                'price' => $validated['price'],
                'pricing_type' => $validated['pricing_type'] ?? $menuModel->pricing_type,
                'is_available' => $validated['is_available'] ?? true,
                'is_recommended' => $validated['is_recommended'] ?? false,
            ]);

            $menuModel->addonGroups()->delete();
            $this->syncAddonGroups($menuModel, $validated['addon_groups'] ?? []);

            if (array_key_exists('sales_channel_ids', $validated)) {
                $this->salesChannels->syncMenuChannels(
                    $menuModel,
                    $validated['sales_channel_ids'] ?? [SalesChannel::store()->id],
                );
            }
        });

        return redirect()
            ->route('admin.menus.show', $menuModel)
            ->with('success', 'Menu updated successfully.');
    }

    /**
     * @return list<array{id: int, name: string, type: string}>
     */
    private function channelOptions(): array
    {
        return array_map(
            fn (SalesChannel $channel) => [
                'id' => $channel->id,
                'name' => $channel->name,
                'type' => $channel->type,
            ],
            $this->salesChannels->listForIndex(),
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $addonGroups
     */
    private function syncAddonGroups(MenuModel $menu, array $addonGroups): void
    {
        foreach ($addonGroups as $groupIndex => $groupData) {
            $group = MenuAddonGroup::query()->create([
                'menu_id' => $menu->id,
                'name' => $groupData['name'],
                'selection_type' => $groupData['selection_type'],
                'is_required' => $groupData['is_required'] ?? false,
                'sort_order' => $groupIndex,
            ]);

            foreach ($groupData['options'] as $optionIndex => $optionData) {
                MenuAddonOption::query()->create([
                    'menu_addon_group_id' => $group->id,
                    'name' => $optionData['name'],
                    'price' => $optionData['price'],
                    'is_available' => $optionData['is_available'] ?? true,
                    'sort_order' => $optionIndex,
                ]);
            }
        }
    }
}
