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
        Schema::create('relationships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')->constrained('people')->onDelete('cascade');
            $table->foreignId('related_person_id')->constrained('people')->onDelete('cascade');

            $table->enum('relationship_type', [
                'father',
                'mother',
                'son',
                'daughter',
                'brother',
                'sister',
                'husband',
                'wife',
                'grandfather',
                'grandmother',
                'uncle',
                'aunt',
                'nephew',
                'niece',
                'cousin',
            ]);

            $table->text('notes')->nullable(); // Additional information about the relationship
            $table->timestamps();

            // Indexes
            $table->index('person_id');
            $table->index('related_person_id');
            $table->index('relationship_type');
            $table->unique(['person_id', 'related_person_id', 'relationship_type'], 'unique_relationship');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('relationships');
    }
};
