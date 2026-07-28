<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('home_sections', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->boolean('is_active')->default(true);
            $table->integer('display_order')->default(0)->index();
            $table->timestamps();
        });

        Schema::create('home_section_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('home_section_id')
                ->constrained('home_sections')
                ->cascadeOnDelete();
            $table->string('locale', 5)->index();
            $table->string('heading')->nullable();
            $table->text('subheading')->nullable();
            $table->boolean('is_ready')->default(false);
            $table->timestamps();

            $table->unique(['home_section_id', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('home_section_translations');
        Schema::dropIfExists('home_sections');
    }
};
