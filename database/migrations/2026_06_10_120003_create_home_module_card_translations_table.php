<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('home_module_card_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('home_module_card_id')
                ->constrained('home_module_cards')
                ->cascadeOnDelete();
            $table->string('locale', 5)->index();
            $table->string('title', 255)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_ready')->default(false);
            $table->timestamps();

            $table->unique(['home_module_card_id', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('home_module_card_translations');
    }
};
