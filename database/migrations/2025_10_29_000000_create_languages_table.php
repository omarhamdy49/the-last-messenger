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
            $table->string('code', 5)->unique()->comment('Language code (ar, en, fr, tr, ur)');
            $table->json('name')->comment('Language name in all languages');
            $table->string('native_name', 100)->comment('Language name in native script');
            $table->enum('direction', ['ltr', 'rtl'])->default('ltr')->comment('Text direction');
            $table->boolean('is_active')->default(true)->comment('Whether language is active');
            $table->boolean('is_default')->default(false)->comment('Whether this is the default language');
            $table->integer('sort_order')->default(0)->comment('Display order');
            $table->string('flag_icon', 50)->nullable()->comment('Flag icon/emoji');
            $table->timestamps();

            // Indexes
            $table->index('code');
            $table->index('is_active');
            $table->index('is_default');
            $table->index('sort_order');
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
