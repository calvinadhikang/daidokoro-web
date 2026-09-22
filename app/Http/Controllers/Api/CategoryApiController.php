<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreApiCategoryRequest;
use App\Models\Category;
use App\Services\MenuCatalogService;
use App\Services\SalesChannelService;
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
