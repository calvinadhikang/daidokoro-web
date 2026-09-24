<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->date('business_date')->default('1970-01-01')->after('id');
            $table->unsignedSmallInteger('daily_number')->default(0)->after('business_date');
        });

        $counters = [];

        DB::table('transactions')
            ->orderBy('id')
            ->get(['id', 'created_at'])
            ->each(function (object $transaction) use (&$counters): void {
                $date = Carbon::parse($transaction->created_at)
                    ->timezone('Asia/Jakarta')
                    ->toDateString();

                $counters[$date] = ($counters[$date] ?? 0) + 1;

                DB::table('transactions')->where('id', $transaction->id)->update([
                    'business_date' => $date,
                    'daily_number' => $counters[$date],
                ]);
            });

        Schema::table('transactions', function (Blueprint $table) {
            $table->unique(['business_date', 'daily_number']);
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropUnique(['business_date', 'daily_number']);
            $table->dropColumn(['business_date', 'daily_number']);
        });
    }
};
