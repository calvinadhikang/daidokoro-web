<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\MenuModel;
use Database\Seeders\MenuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_menu_seeder_skips_special_uni_and_hampers_platter(): void
    {
        $this->seed(MenuSeeder::class);

        $this->assertFalse(
            Category::query()->where('name', 'Special Uni')->exists(),
        );
        $this->assertFalse(
            Category::query()->where('name', 'Hampers Platter')->exists(),
        );

        $this->assertFalse(MenuModel::query()->where('name', 'Uni Gunkan')->exists());
        $this->assertFalse(MenuModel::query()->where('name', 'Platter 3 (38 Pcs)')->exists());
        $this->assertFalse(MenuModel::query()->where('name', 'Platter 4 (44 Pcs)')->exists());

        $this->assertTrue(Category::query()->where('name', 'Rice Bowl')->exists());
        $this->assertTrue(MenuModel::query()->where('name', 'Rice Bowl Salmon')->exists());
    }
}
