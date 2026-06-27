<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use App\Models\MenuModel;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(): Response
    {
        $categories = Category::query()
            ->withCount('menus')
            ->with(['menus' => fn ($query) => $query->select('menus.id', 'menus.name')])
            ->orderBy('name')
            ->get();

        return Inertia::render('admin/categories/index', [
            'categories' => $categories,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/categories/create', [
            'menus' => $this->menuOptions(),
        ]);
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $category = Category::query()->create([
            'name' => $validated['name'],
        ]);

        $category->menus()->sync($validated['menu_ids'] ?? []);

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Category created successfully.');
    }

    public function show(Category $category): Response
    {
        $category->load(['menus' => fn ($query) => $query->select('menus.id', 'menus.name', 'menus.is_available')]);

        return Inertia::render('admin/categories/show', [
            'category' => $category,
            'menus' => $this->menuOptions(),
        ]);
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $validated = $request->validated();

        $category->update([
            'name' => $validated['name'],
        ]);

        $category->menus()->sync($validated['menu_ids'] ?? []);

        return redirect()
            ->route('admin.categories.show', $category)
            ->with('success', 'Category updated successfully.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $category->delete();

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Category deleted successfully.');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, MenuModel>
     */
    private function menuOptions()
    {
        return MenuModel::query()
            ->select('id', 'name', 'is_available')
            ->orderByDesc('is_available')
            ->orderBy('name')
            ->get();
    }
}
