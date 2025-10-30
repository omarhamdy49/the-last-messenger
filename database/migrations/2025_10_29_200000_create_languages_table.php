<?php

declare(strict_types=1);

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
        Schema::create('languages', function (Blueprint $table) {
            $table->id();
            $table->string('code', 5)->unique()->comment('Language code: ar, en, fr, tr, ur');
            $table->json('name')->comment('Language name in all languages');
            $table->string('native_name', 100)->comment('Language name in its native script');
            $table->enum('direction', ['ltr', 'rtl'])->default('ltr')->comment('Text direction');
            $table->boolean('is_active')->default(true)->index()->comment('Is language available in the system');
            $table->boolean('is_default')->default(false)->index()->comment('Is this the default language');
            $table->unsignedSmallInteger('sort_order')->default(0)->index()->comment('Display order in language selector');
            $table->string('flag_icon', 10)->nullable()->comment('Flag emoji or icon code');
            $table->timestamps();

            // Indexes
            $table->index(['is_active', 'sort_order'], 'idx_active_sort');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('languages');
    }
};
