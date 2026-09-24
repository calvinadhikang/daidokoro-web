<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AssignCategoryMenuRequest;
use App\Http\Requests\StoreApiCategoryRequest;
use App\Models\Category;
use App\Models\MenuModel;
use App\Services\MenuCatalogService;
use App\Services\SalesChannelService;
use App\Support\PriceLabel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CategoryApiController extends Controller
{
    public function __construct(
        private MenuCatalogService $menuCatalog,
        private SalesChannelService $salesChannels,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $channelId = $request->integer('sales_channel_id') ?: null;
        $categories = $channelId === null
            ? $this->menuCatalog->categoriesForFilters()
            : $this->menuCatalog->categoriesForBrowse($this->salesChannels->resolve($channelId));

        return response()->json(
            $categories
                ->map(fn (Category $category) => $this->format($category))
                ->values()
        );
    }

    public function store(StoreApiCategoryRequest $request): JsonResponse
    {
        $category = Category::query()->create([
            'name' => trim($request->validated('name')),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Category created successfully.',
            'category' => $this->format($category->loadCount('menus')),
        ], 201);
    }

    public function show(Category $category): JsonResponse
    {
        $this->assertAssignable($category);

        $assignedIds = array_fill_keys(
            $category->menus()->pluck('menus.id')->map(fn ($id) => (int) $id)->all(),
            true,
        );

        $menus = MenuModel::query()
            ->orderByDesc('is_available')
            ->orderBy('name')
            ->get(['id', 'name', 'price', 'pricing_type', 'is_available']);

        return response()->json([
            'category' => $this->format($category->loadCount('menus')),
            'menus' => $menus->map(function (MenuModel $menu) use ($assignedIds) {
                $pricingType = $menu->pricing_type ?: MenuModel::PRICING_STANDARD;

                return [
                    'id' => $menu->id,
                    'name' => $menu->name,
                    'price' => (int) $menu->price,
                    'price_label' => PriceLabel::format((int) $menu->price, $pricingType),
                    'is_available' => (bool) $menu->is_available,
                    'assigned' => isset($assignedIds[$menu->id]),
                ];
            })->values(),
        ]);
    }

    public function assignMenu(AssignCategoryMenuRequest $request, Category $category): JsonResponse
    {
        $this->assertAssignable($category);

        $category->menus()->syncWithoutDetaching([(int) $request->validated('menu_id')]);
        $category->loadCount('menus');

        return response()->json([
            'success' => true,
            'assigned' => true,
            'category' => $this->format($category),
        ]);
    }

    public function unassignMenu(AssignCategoryMenuRequest $request, Category $category): JsonResponse
    {
        $this->assertAssignable($category);

        $category->menus()->detach((int) $request->validated('menu_id'));
        $category->loadCount('menus');

        return response()->json([
            'success' => true,
            'assigned' => false,
            'category' => $this->format($category),
        ]);
    }

    public function update(StoreApiCategoryRequest $request, Category $category): JsonResponse
    {
        $this->assertAssignable($category);

        $category->update([
            'name' => trim($request->validated('name')),
        ]);
        $category->refresh();
        $category->loadCount('menus');

        return response()->json([
            'success' => true,
            'message' => 'Category updated successfully.',
            'category' => $this->format($category),
        ]);
    }

    public function destroy(Category $category): JsonResponse
    {
        $this->assertAssignable($category);

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully.',
        ]);
    }

    /**
     * @return array{id: int, name: string, menus_count: int}
     */
    private function format(Category $category): array
    {
        return [
            'id' => $category->id,
            'name' => $category->name,
            'menus_count' => (int) ($category->menus_count ?? $category->menus()->count()),
        ];
    }

    private function assertAssignable(Category $category): void
    {
        if (Category::isHardcodedRecommended($category->name)) {
            throw ValidationException::withMessages([
                'name' => 'The Recommended label is reserved.',
            ]);
        }
    }
}
