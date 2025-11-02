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
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->string('filename');
            $table->string('original_filename');
            $table->string('path');
            $table->string('mime_type');
            $table->unsignedBigInteger('size'); // in bytes
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->string('disk')->default('public');
            $table->text('alt_text')->nullable();
            $table->text('caption')->nullable();
            $table->json('metadata')->nullable(); // Store EXIF, optimization info, etc.
            $table->string('type')->default('image'); // image, video, document, etc.
            $table->morphs('mediable'); // polymorphic relationship
            $table->string('collection_name')->default('default');
            $table->timestamps();
            $table->softDeletes();

            // Indexes (morphs already creates index for mediable_type and mediable_id)
            $table->index('collection_name');
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
