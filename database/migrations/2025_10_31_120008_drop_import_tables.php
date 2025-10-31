<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop tables in reverse order of creation (due to foreign keys)
        Schema::dropIfExists('import_mappings');
        Schema::dropIfExists('import_errors');
        Schema::dropIfExists('imports');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // We're not recreating these tables as the import system is being removed
        // This is a one-way migration
    }
};
