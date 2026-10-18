<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'daidokoro'],
            [
                'name' => 'Daidokoro',
                'password' => 'daidokoro',
            ],
        );

        $this->call(MenuSeeder::class);
        $this->call(MenuAddonSeeder::class);
        $this->call(OmakaseSeeder::class);
    }
}
