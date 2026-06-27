<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('operating_hours', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('day_of_week')->unique();
            $table->boolean('is_closed')->default(false);
            $table->time('session_1_starts_at')->nullable();
            $table->time('session_1_ends_at')->nullable();
            $table->time('session_2_starts_at')->nullable();
            $table->time('session_2_ends_at')->nullable();
            $table->timestamps();
        });

        Schema::create('operating_closures', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->string('label')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('operating_closures');
        Schema::dropIfExists('operating_hours');
    }
};
