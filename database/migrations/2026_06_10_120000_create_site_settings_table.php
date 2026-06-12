<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Site settings store the editable global chrome of the public site
 * (site name, hero copy, footer text, social URLs, SEO, etc).
 *
 * Translatable settings (text|textarea|richtext) keep their per-locale value
 * in `site_setting_translations`. Non-translatable settings (url|image|bool)
 * store their single value directly in the `value` column.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('group')->default('general');
            // text | textarea | richtext | image | url | bool
            $table->string('type', 20)->default('text');
            // Non-translatable value (urls/images/bools). Null for translatable types.
            $table->text('value')->nullable();
            $table->boolean('is_translatable')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('group');
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};
