<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\ManagesMenuImages;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMenuRequest;
use App\Http\Requests\UpdateMenuRequest;
use App\Models\MenuAddonGroup;
use App\Models\MenuAddonOption;
use App\Models\MenuModel;
use App\Services\MenuImageService;
use App\Support\MenuApiFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class MenuApiController extends Controller
{
    use ManagesMenuImages;

    public function __construct(
        private MenuImageService $menuImages,
    ) {}

    public function index(): JsonResponse
    {
        $menus = MenuModel::query()
            ->with(['categories:id,name', 'addonGroups.options'])
            ->orderByDesc('is_available')
            ->orderByDesc('is_recommended')
            ->orderBy('name')
            ->get();

        return response()->json(
            $menus
                ->map(fn (MenuModel $menu) => MenuApiFormatter::formatListItem($menu))
                ->values()
        );
    }

    public function show(MenuModel $menuModel): JsonResponse
    {
        $menuModel->load(['categories:id,name', 'addonGroups.options']);

        return response()->json(MenuApiFormatter::formatDetail($menuModel));
    }

    public function store(StoreMenuRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $menu = DB::transaction(function () use ($validated, $request) {
            $menu = MenuModel::query()->create([
                'name' => $validated['name'],
                'image' => $this->resolveMenuImageUrl($request, $this->menuImages),
                'price' => $validated['price'],
                'is_available' => $validated['is_available'] ?? true,
                'is_recommended' => $validated['is_recommended'] ?? false,
            ]);

            $this->syncAddonGroups($menu, $validated['addon_groups'] ?? []);
            $menu->categories()->sync($validated['category_ids'] ?? []);

            return $menu;
        });

        $menu->load(['categories:id,name', 'addonGroups.options']);

        return response()->json([
            'success' => true,
            'menu' => MenuApiFormatter::formatDetail($menu),
        ], 201);
    }

    public function update(UpdateMenuRequest $request, MenuModel $menuModel): JsonResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($menuModel, $validated, $request) {
            $menuModel->update([
                'name' => $validated['name'],
                'image' => $this->resolveMenuImageUrl($request, $this->menuImages, $menuModel->image),
                'price' => $validated['price'],
                'is_available' => $validated['is_available'] ?? true,
                'is_recommended' => $validated['is_recommended'] ?? false,
            ]);

            $menuModel->addonGroups()->delete();
            $this->syncAddonGroups($menuModel, $validated['addon_groups'] ?? []);
            $menuModel->categories()->sync($validated['category_ids'] ?? []);
        });

        $menuModel->load(['categories:id,name', 'addonGroups.options']);

        return response()->json([
            'success' => true,
            'menu' => MenuApiFormatter::formatDetail($menuModel),
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

    public function toggleAvailability(MenuModel $menuModel): JsonResponse
    {
        $menuModel->update([
            'is_available' => ! $menuModel->is_available,
        ]);

        $menuModel->load(['categories:id,name', 'addonGroups.options']);

        return response()->json([
            'success' => true,
            'message' => $menuModel->name.' is now '.($menuModel->is_available ? 'available' : 'unavailable').'.',
            'menu' => MenuApiFormatter::formatListItem($menuModel),
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
