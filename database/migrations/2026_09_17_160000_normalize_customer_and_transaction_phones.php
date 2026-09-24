<?php

use App\Services\CustomerDirectoryService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        app(CustomerDirectoryService::class)->normalizeAndMergeStoredPhones();
    }

    public function down(): void
    {
        // Phone normalization and duplicate customer merges cannot be reversed.
    }
};
