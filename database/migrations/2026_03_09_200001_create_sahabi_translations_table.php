<?php

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
        Schema::create('sahabi_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sahabi_id')
                ->constrained('sahaba')
                ->cascadeOnDelete();
            $table->string('locale', 5)->index();
            $table->string('name', 255)->nullable();
            $table->string('full_name', 500)->nullable();
            $table->string('kunya', 255)->nullable();
            $table->string('laqab', 255)->nullable();
            $table->longText('biography')->nullable();
            $table->longText('virtues')->nullable();
            $table->longText('famous_narrations')->nullable();
            $table->boolean('is_ready')->default(false);
            $table->timestamps();

            $table->unique(['sahabi_id', 'locale']);
        });

        // Add FULLTEXT index (MySQL-specific)
        if (config('database.default') === 'mysql') {
            \Illuminate\Support\Facades\DB::statement(
                'ALTER TABLE sahabi_translations ADD FULLTEXT fulltext_name_fields (name, full_name, kunya, laqab)'
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sahabi_translations');
    }
};
