<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMenuRequest;
use App\Http\Requests\UpdateMenuRequest;
use App\Models\MenuAddonGroup;
use App\Models\MenuAddonOption;
use App\Models\MenuModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class MenuController extends Controller
{
    public function index(): Response
    {
        $menus = MenuModel::query()
            ->with(['addonGroups.options'])
            ->orderByDesc('is_available')
            ->orderBy('name')
            ->get();

        return Inertia::render('admin/menus/index', [
            'menus' => $menus,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/menus/create');
    }

    public function store(StoreMenuRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated) {
            $menu = MenuModel::query()->create([
                'name' => $validated['name'],
                'price' => $validated['price'],
                'is_available' => $validated['is_available'] ?? true,
                'is_recommended' => $validated['is_recommended'] ?? false,
            ]);

            $this->syncAddonGroups($menu, $validated['addon_groups'] ?? []);
        });

        return redirect()
            ->route('admin.menus.index')
            ->with('success', 'Menu created successfully.');
    }

    public function show(MenuModel $menuModel): Response
    {
        $menuModel->load(['addonGroups.options']);

        return Inertia::render('admin/menus/show', [
            'menu' => $menuModel,
        ]);
    }

    public function update(UpdateMenuRequest $request, MenuModel $menuModel): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($menuModel, $validated) {
            $menuModel->update([
                'name' => $validated['name'],
                'price' => $validated['price'],
                'is_available' => $validated['is_available'] ?? true,
                'is_recommended' => $validated['is_recommended'] ?? false,
            ]);

            $menuModel->addonGroups()->delete();
            $this->syncAddonGroups($menuModel, $validated['addon_groups'] ?? []);
        });

        return redirect()
            ->route('admin.menus.show', $menuModel)
            ->with('success', 'Menu updated successfully.');
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
