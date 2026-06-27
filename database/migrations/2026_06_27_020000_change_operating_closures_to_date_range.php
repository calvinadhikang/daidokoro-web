<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('operating_closures', function (Blueprint $table) {
            $table->date('starts_at')->nullable()->after('id');
            $table->date('ends_at')->nullable()->after('starts_at');
        });

        foreach (DB::table('operating_closures')->orderBy('id')->get() as $closure) {
            DB::table('operating_closures')
                ->where('id', $closure->id)
                ->update([
                    'starts_at' => $closure->date,
                    'ends_at' => $closure->date,
                ]);
        }

        Schema::table('operating_closures', function (Blueprint $table) {
            $table->dropUnique(['date']);
            $table->dropColumn('date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('operating_closures', function (Blueprint $table) {
            $table->date('date')->nullable()->after('id');
        });

        foreach (DB::table('operating_closures')->orderBy('id')->get() as $closure) {
            DB::table('operating_closures')
                ->where('id', $closure->id)
                ->update(['date' => $closure->starts_at]);
        }

        Schema::table('operating_closures', function (Blueprint $table) {
            $table->date('date')->nullable(false)->change();
            $table->unique('date');
            $table->dropColumn(['starts_at', 'ends_at']);
        });
    }
};
