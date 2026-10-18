<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'daidokoro'],
            [
                'name' => 'Daidokoro',
                'password' => 'daidokoro',
            ],
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        User::query()->where('email', 'daidokoro')->delete();
    }
};
