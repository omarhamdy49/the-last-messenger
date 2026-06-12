<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media_categories', function (Blueprint $table) {
            // Per-locale display overrides; admin/public fall back to the
            // media.public.category_{key} lang entries when null.
            $table->json('name')->nullable()->after('key');
            $table->json('description')->nullable()->after('name');
            $table->string('icon', 50)->nullable()->after('description');
            $table->string('color', 20)->nullable()->after('icon');
        });
    }

    public function down(): void
    {
        Schema::table('media_categories', function (Blueprint $table) {
            $table->dropColumn(['name', 'description', 'icon', 'color']);
        });
    }
};
