<?php

namespace Tests\Feature;

use App\Models\MenuModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MenuImageUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAsAdmin();
        Storage::fake('public');
    }

    public function test_admin_can_create_menu_with_image(): void
    {
        $image = UploadedFile::fake()->image('sundae.jpg');

        $response = $this->post(route('admin.menus.store'), [
            'name' => 'Sundae',
            'price' => 25000,
            'is_available' => true,
            'is_recommended' => false,
            'addon_groups' => [],
            'image' => $image,
        ]);

        $response->assertRedirect(route('admin.menus.index'));

        $menu = MenuModel::query()->where('name', 'Sundae')->firstOrFail();

        $this->assertNotNull($menu->image);
        $this->assertStringContainsString('/storage/menus/', $menu->image);
        $this->assertPublicMenuImageExists($menu->image);
    }

    public function test_admin_can_replace_menu_image(): void
    {
        $menu = MenuModel::query()->create([
            'name' => 'Coffee',
            'image' => 'https://example.com/old.jpg',
            'price' => 15000,
            'is_available' => true,
        ]);

        $image = UploadedFile::fake()->image('coffee.png');

        $response = $this->put(route('admin.menus.update', $menu), [
            'name' => 'Coffee',
            'price' => 15000,
            'is_available' => true,
            'is_recommended' => false,
            'addon_groups' => [],
            'image' => $image,
        ]);

        $response->assertRedirect(route('admin.menus.show', $menu));

        $menu->refresh();

        $this->assertNotNull($menu->image);
        $this->assertStringContainsString('/storage/menus/', $menu->image);
        $this->assertStringContainsString('.png', $menu->image);
    }

    public function test_api_can_create_menu_with_image(): void
    {
        $image = UploadedFile::fake()->image('latte.webp');

        $response = $this->post('/api/menu/create', [
            'name' => 'Matcha Latte',
            'price' => 32000,
            'is_available' => true,
            'image' => $image,
        ]);

        $response->assertCreated();
        $response->assertJsonPath('menu.name', 'Matcha Latte');

        $menu = MenuModel::query()->where('name', 'Matcha Latte')->firstOrFail();

        $this->assertNotNull($menu->image);
        $this->assertPublicMenuImageExists($menu->image);
    }

    public function test_api_can_replace_menu_image(): void
    {
        $this->post('/api/menu/create', [
            'name' => 'Coffee',
            'price' => 15000,
            'is_available' => true,
            'image' => UploadedFile::fake()->image('old.jpg'),
        ])->assertCreated();

        $menu = MenuModel::query()->where('name', 'Coffee')->firstOrFail();
        $previousImage = $menu->image;

        $this->post('/api/menu/update/'.$menu->id, [
            'name' => 'Coffee',
            'price' => 15000,
            'is_available' => true,
            'is_recommended' => false,
            'image' => UploadedFile::fake()->image('new.png'),
        ])->assertOk();

        $menu->refresh();

        $this->assertNotNull($menu->image);
        $this->assertNotSame($previousImage, $menu->image);
        $this->assertStringContainsString('.png', $menu->image);
        $this->assertPublicMenuImageExists($menu->image);
    }

    public function test_api_can_remove_menu_image(): void
    {
        $this->post('/api/menu/create', [
            'name' => 'Tea',
            'price' => 12000,
            'is_available' => true,
            'image' => UploadedFile::fake()->image('tea.jpg'),
        ])->assertCreated();

        $menu = MenuModel::query()->where('name', 'Tea')->firstOrFail();

        $this->postJson('/api/menu/update/'.$menu->id, [
            'name' => 'Tea',
            'price' => 12000,
            'is_available' => true,
            'remove_image' => true,
        ])->assertOk();

        $menu->refresh();

        $this->assertNull($menu->image);
    }

    public function test_menu_image_validation_rejects_invalid_file(): void
    {
        $response = $this->postJson('/api/menu/create', [
            'name' => 'Bad Upload',
            'price' => 10000,
            'is_available' => true,
            'image' => UploadedFile::fake()->create('notes.txt', 10, 'text/plain'),
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['image']);
    }

    private function assertPublicMenuImageExists(string $imageUrl): void
    {
        $path = 'menus/'.basename((string) parse_url($imageUrl, PHP_URL_PATH));

        $this->assertTrue(Storage::disk('public')->exists($path));
    }
}
