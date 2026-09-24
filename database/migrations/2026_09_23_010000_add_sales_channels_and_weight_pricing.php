<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_channels', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->string('name');
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->boolean('closes_store')->default(true);
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
        });

        $now = now();
        $storeId = DB::table('sales_channels')->insertGetId([
            'type' => 'store',
            'name' => 'Toko',
            'starts_at' => null,
            'ends_at' => null,
            'closes_store' => false,
            'archived_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        Schema::create('channel_menu', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_channel_id')->constrained('sales_channels')->cascadeOnDelete();
            $table->foreignId('menu_id')->constrained('menus')->cascadeOnDelete();
            $table->unsignedInteger('price_override')->nullable();
            $table->boolean('is_available')->default(true);
            $table->timestamps();
            $table->unique(['sales_channel_id', 'menu_id']);
        });

        $menuRows = DB::table('menus')->get(['id', 'is_available']);

        foreach ($menuRows as $menu) {
            DB::table('channel_menu')->insert([
                'sales_channel_id' => $storeId,
                'menu_id' => $menu->id,
                'price_override' => null,
                'is_available' => (bool) $menu->is_available,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        Schema::table('menus', function (Blueprint $table) {
            $table->string('pricing_type')->default('standard')->after('price');
        });

        Schema::table('transactions', function (Blueprint $table) use ($storeId) {
            $table->unsignedBigInteger('sales_channel_id')->default($storeId)->after('id');
            $table->foreign('sales_channel_id')
                ->references('id')
                ->on('sales_channels')
                ->restrictOnDelete();
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropUnique(['business_date', 'daily_number']);
            $table->unique(['business_date', 'sales_channel_id', 'daily_number']);
        });

        Schema::table('transaction_items', function (Blueprint $table) {
            $table->unsignedInteger('weight_grams')->nullable()->after('quantity');
            $table->string('pricing_type')->default('standard')->after('weight_grams');
        });

        Schema::table('operating_closures', function (Blueprint $table) {
            $table->foreignId('sales_channel_id')
                ->nullable()
                ->after('id')
                ->constrained('sales_channels')
                ->nullOnDelete();
            $table->unique('sales_channel_id');
        });
    }

    public function down(): void
    {
        Schema::table('operating_closures', function (Blueprint $table) {
            $table->dropUnique(['sales_channel_id']);
            $table->dropConstrainedForeignId('sales_channel_id');
        });

        Schema::table('transaction_items', function (Blueprint $table) {
            $table->dropColumn(['weight_grams', 'pricing_type']);
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropUnique(['business_date', 'sales_channel_id', 'daily_number']);
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sales_channel_id');
            $table->unique(['business_date', 'daily_number']);
        });

        Schema::table('menus', function (Blueprint $table) {
            $table->dropColumn('pricing_type');
        });

        Schema::dropIfExists('channel_menu');
        Schema::dropIfExists('sales_channels');
    }
};
