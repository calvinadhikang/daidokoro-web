<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\MenuModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class MenuSeeder extends Seeder
{
    /**
     * Categories present in the CSV that should not be seeded.
     *
     * @var list<string>
     */
    private const SKIPPED_CATEGORIES = [
        'Special Uni',
        'Hampers Platter',
    ];

    /**
     * Seed categories and menus from the products CSV.
     *
     * Clears existing menus/categories first so re-runs (e.g. staging deploy) stay idempotent.
     */
    public function run(): void
    {
        $path = database_path('data/products.csv');

        if (! is_readable($path)) {
            throw new RuntimeException("Products CSV not found or unreadable at {$path}");
        }

        $handle = fopen($path, 'r');

        if ($handle === false) {
            throw new RuntimeException("Unable to open products CSV at {$path}");
        }

        try {
            $header = fgetcsv($handle, 0, ';');

            if ($header === false) {
                throw new RuntimeException('Products CSV is empty');
            }

            $header = array_map(static fn (string $column): string => trim($column), $header);
            $indexes = array_flip($header);

            foreach (['Category', 'Name', 'Price', 'Status'] as $required) {
                if (! array_key_exists($required, $indexes)) {
                    throw new RuntimeException("Products CSV missing required column: {$required}");
                }
            }

            Schema::disableForeignKeyConstraints();
            MenuModel::query()->delete();
            Category::query()->delete();
            Schema::enableForeignKeyConstraints();

            /** @var array<string, Category> $categories */
            $categories = [];

            while (($row = fgetcsv($handle, 0, ';')) !== false) {
                $name = trim((string) ($row[$indexes['Name']] ?? ''));

                if ($name === '') {
                    continue;
                }

                $categoryName = trim((string) ($row[$indexes['Category']] ?? ''));

                if ($this->shouldSkipCategory($categoryName)) {
                    continue;
                }

                $price = (int) ($row[$indexes['Price']] ?? 0);
                $status = strtoupper(trim((string) ($row[$indexes['Status']] ?? 'ACTIVE')));
                $isRecommended = Category::isHardcodedRecommended($categoryName);

                if (
                    $categoryName !== ''
                    && ! $isRecommended
                    && ! isset($categories[$categoryName])
                ) {
                    $categories[$categoryName] = Category::query()->create([
                        'name' => $categoryName,
                    ]);
                }

                $menu = MenuModel::query()->create([
                    'name' => $name,
                    'image' => null,
                    'price' => $price,
                    'is_available' => $status === 'ACTIVE',
                    'is_recommended' => $isRecommended,
                ]);

                if (
                    $categoryName !== ''
                    && ! $isRecommended
                    && isset($categories[$categoryName])
                ) {
                    $menu->categories()->attach($categories[$categoryName]->id);
                }
            }
        } finally {
            fclose($handle);
        }
    }

    private function shouldSkipCategory(string $name): bool
    {
        foreach (self::SKIPPED_CATEGORIES as $skipped) {
            if (strcasecmp($name, $skipped) === 0) {
                return true;
            }
        }

        return false;
    }
}
