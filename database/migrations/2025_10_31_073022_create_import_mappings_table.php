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
        Schema::create('import_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_id')->constrained()->onDelete('cascade');
            $table->string('csv_column');
            $table->string('db_field');
            $table->string('transform_rule')->nullable(); // date_format, uppercase, etc.
            $table->integer('column_index')->unsigned();
            $table->timestamps();

            // Composite unique index
            $table->unique(['import_id', 'csv_column']);
            $table->index('import_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_mappings');
    }
};
