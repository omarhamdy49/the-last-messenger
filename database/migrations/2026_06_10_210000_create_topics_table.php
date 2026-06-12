<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('topics', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('icon', 50)->default('bookmark');
            $table->string('color', 30)->nullable();
            $table->boolean('is_featured')->default(false)->index();
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->timestamps();

            $table->index(['is_featured', 'display_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('topics');
    }
};
