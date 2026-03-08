<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('media_size_presets', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->unsignedInteger('width');
            $table->unsignedInteger('height');
            $table->string('method', 20)->default('fit');
            $table->unsignedTinyInteger('quality')->default(85);
            $table->boolean('enabled')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        DB::table('media_size_presets')->insert([
            [
                'name' => 'thumbnail',
                'width' => 150,
                'height' => 150,
                'method' => 'crop',
                'quality' => 85,
                'enabled' => true,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'medium',
                'width' => 400,
                'height' => 300,
                'method' => 'fit',
                'quality' => 85,
                'enabled' => true,
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'large',
                'width' => 1200,
                'height' => 800,
                'method' => 'fit',
                'quality' => 85,
                'enabled' => true,
                'sort_order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media_size_presets');
    }
};
