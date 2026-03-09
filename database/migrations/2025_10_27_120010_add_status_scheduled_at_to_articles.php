<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->enum('status', ['draft', 'pending', 'published', 'scheduled', 'archived'])
                  ->default('draft')
                  ->after('hijri_date')
                  ->comment('Publication status');

            $table->timestamp('scheduled_at')
                  ->nullable()
                  ->after('status')
                  ->comment('Scheduled publish datetime');

            $table->index('status');
            $table->index('scheduled_at');
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['scheduled_at']);
            $table->dropColumn(['status', 'scheduled_at']);
        });
    }
};
