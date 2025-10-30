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
        Schema::create('seerah_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('seerah_events')->onDelete('cascade');
            $table->string('participant_type'); // 'person', 'group', 'tribe'
            $table->unsignedBigInteger('participant_id')->nullable(); // ID from other tables (optional)
            $table->json('name'); // Multi-language name
            $table->enum('role', ['prophet', 'companion', 'enemy', 'ally', 'witness', 'other'])->default('other');
            $table->text('description')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('event_id');
            $table->index(['participant_type', 'participant_id']);
            $table->index('role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seerah_participants');
    }
};
