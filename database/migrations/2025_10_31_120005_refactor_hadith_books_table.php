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
        Schema::table('hadith_books', function (Blueprint $table) {
            // Drop old collection_name index
            if (Schema::hasColumn('hadith_books', 'collection_name')) {
                $table->dropIndex(['collection_name']);
            }

            // Add collection_id foreign key before removing collection_name
            $table->foreignId('collection_id')
                ->nullable()
                ->after('slug')
                ->constrained('hadith_collections')
                ->onDelete('set null');

            // Add active flag
            $table->boolean('active')->default(true)->after('display_order');
        });

        // Drop collection_name after foreign key is in place
        Schema::table('hadith_books', function (Blueprint $table) {
            if (Schema::hasColumn('hadith_books', 'collection_name')) {
                $table->dropColumn('collection_name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hadith_books', function (Blueprint $table) {
            // Remove active flag
            if (Schema::hasColumn('hadith_books', 'active')) {
                $table->dropColumn('active');
            }

            // Remove foreign key and collection_id
            if (Schema::hasColumn('hadith_books', 'collection_id')) {
                $table->dropForeignKey(['collection_id']);
                $table->dropColumn('collection_id');
            }

            // Re-add collection_name
            $table->string('collection_name')->nullable()->after('slug');
            $table->index('collection_name');
        });
    }
};
