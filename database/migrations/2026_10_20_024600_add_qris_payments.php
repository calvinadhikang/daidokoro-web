<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('paid_via', 20)->nullable()->after('status');
        });

        Schema::create('qris_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained()->cascadeOnDelete();
            $table->string('partner_reference_no', 64)->unique();
            $table->string('external_id', 36)->unique();
            $table->string('reference_no', 64)->nullable();
            $table->text('qr_content');
            $table->unsignedInteger('amount');
            $table->timestamp('expires_at');
            $table->string('status', 20)->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->json('notify_payload')->nullable();
            $table->timestamps();

            $table->index(['transaction_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qris_payments');

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('paid_via');
        });
    }
};
