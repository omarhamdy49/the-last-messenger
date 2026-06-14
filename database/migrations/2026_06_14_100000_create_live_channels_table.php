<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('live_channels', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('streaming_url', 1000)->nullable();
            $table->string('status', 20)->default('offline')->index();
            $table->boolean('is_published')->default(true)->index();
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->unsignedInteger('views_count')->default(0);
            $table->timestamps();

            $table->index(['is_published', 'display_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('live_channels');
    }
};
