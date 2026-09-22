<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\ManagesMenuImages;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMenuRequest;
use App\Http\Requests\UpdateMenuRequest;
use App\Models\MenuAddonGroup;
use App\Models\MenuAddonOption;
use App\Models\MenuModel;
use App\Services\MenuCatalogService;
use App\Services\MenuImageService;
use App\Services\SalesChannelService;
use App\Support\MenuApiFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class MenuApiController extends Controller
{
    use ManagesMenuImages;

    public function __construct(
        private MenuImageService $menuImages,
        private MenuCatalogService $menuCatalog,
        private SalesChannelService $salesChannels,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $channel = $this->salesChannels->resolve($request->integer('sales_channel_id') ?: null);
        $menus = $this->menuCatalog->allForBrowse($channel);

        return response()->json(
            $menus
                ->map(fn (MenuModel $menu) => MenuApiFormatter::formatListItem($menu, $channel))
                ->values()
        );
    }

    public function show(Request $request, MenuModel $menuModel): JsonResponse
    {
        $channel = $this->salesChannels->resolve($request->integer('sales_channel_id') ?: null);
        $menuModel->load([
            'categories:id,name',
            'addonGroups.options',
            'salesChannels' => fn ($query) => $query->where('sales_channels.id', $channel->id),
        ]);

        if ($menuModel->salesChannels->isEmpty()) {
            throw new NotFoundHttpException;
        }

        return response()->json(MenuApiFormatter::formatDetail($menuModel, $channel));
    }

    public function store(StoreMenuRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $channel = $this->salesChannels->resolve(
            isset($validated['sales_channel_id']) ? (int) $validated['sales_channel_id'] : null,
        );

        $menu = DB::transaction(function () use ($validated, $request, $channel) {
            $menu = new MenuModel([
                'name' => $validated['name'],
                'image' => $this->resolveMenuImageUrl($request, $this->menuImages),
                'price' => $validated['price'],
                'pricing_type' => $validated['pricing_type'] ?? MenuModel::PRICING_STANDARD,
                'is_available' => $validated['is_available'] ?? true,
                'is_recommended' => $validated['is_recommended'] ?? false,
            ]);
            $menu->assignToSalesChannelId = $channel->id;
            $menu->save();

            $this->syncAddonGroups($menu, $validated['addon_groups'] ?? []);
            $menu->categories()->sync($validated['category_ids'] ?? []);

            return $menu;
        });

        $menu->load([
            'categories:id,name',
            'addonGroups.options',
            'salesChannels' => fn ($query) => $query->where('sales_channels.id', $channel->id),
        ]);

        return response()->json([
            'success' => true,
            'menu' => MenuApiFormatter::formatDetail($menu, $channel),
        ], 201);
    }

    public function update(UpdateMenuRequest $request, MenuModel $menuModel): JsonResponse
    {
        $validated = $request->validated();
        $channel = $this->salesChannels->resolve(
            isset($validated['sales_channel_id']) ? (int) $validated['sales_channel_id'] : null,
        );

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
            $menuModel->categories()->sync($validated['category_ids'] ?? []);
        });

        $menuModel->load([
            'categories:id,name',
            'addonGroups.options',
            'salesChannels' => fn ($query) => $query->where('sales_channels.id', $channel->id),
        ]);

        return response()->json([
            'success' => true,
            'menu' => MenuApiFormatter::formatDetail($menuModel, $channel),
        ]);
    }

    public function destroy(MenuModel $menuModel): JsonResponse
    {
        $menuModel->delete();

        return response()->json([
            'success' => true,
            'message' => 'Menu deleted successfully.',
        ]);
    }

    public function toggleAvailability(Request $request, MenuModel $menuModel): JsonResponse
    {
        $channel = $this->salesChannels->resolve($request->integer('sales_channel_id') ?: null);
        $menuModel->load([
            'salesChannels' => fn ($query) => $query->where('sales_channels.id', $channel->id),
        ]);

        $menu = $this->salesChannels->toggleAvailability($channel, $menuModel);
        $menu->load(['categories:id,name', 'addonGroups.options']);

        $available = $menu->effectiveIsAvailable($channel);

        return response()->json([
            'success' => true,
            'message' => $menu->name.' is now '.($available ? 'available' : 'unavailable').'.',
            'menu' => MenuApiFormatter::formatListItem($menu, $channel),
        ]);
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
