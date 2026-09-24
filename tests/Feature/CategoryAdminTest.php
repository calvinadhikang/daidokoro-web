<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\MenuModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_create_and_assign_menus_on_a_category(): void
    {
        $menu = MenuModel::query()->create([
            'name' => 'Chicken Rice',
            'price' => 35000,
            'is_available' => true,
        ]);

        $this->get(route('admin.categories.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('admin/categories/index'));

        $this->get(route('admin.categories.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/categories/create')
                ->has('menus', 1)
            );

        $this->post(route('admin.categories.store'), [
            'name' => 'Rice bowls',
            'menu_ids' => [$menu->id],
        ])->assertRedirect(route('admin.categories.index'));

        $category = Category::query()->where('name', 'Rice bowls')->firstOrFail();

        $this->assertDatabaseHas('category_menu', [
            'category_id' => $category->id,
            'menu_id' => $menu->id,
        ]);

        $this->get(route('admin.categories.show', $category))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/categories/show')
                ->where('category.name', 'Rice bowls')
                ->has('menus', 1)
            );

        $other = MenuModel::query()->create([
            'name' => 'Miso Soup',
            'price' => 15000,
            'is_available' => true,
        ]);

        $this->put(route('admin.categories.update', $category), [
            'name' => 'Mains',
            'menu_ids' => [$other->id],
        ])->assertRedirect(route('admin.categories.show', $category));

        $this->assertDatabaseMissing('category_menu', [
            'category_id' => $category->id,
            'menu_id' => $menu->id,
        ]);
        $this->assertDatabaseHas('category_menu', [
            'category_id' => $category->id,
            'menu_id' => $other->id,
        ]);
    }
}
