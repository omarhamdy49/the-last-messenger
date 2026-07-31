<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hadith_scholars', function (Blueprint $table): void {
            $table->string('external_id', 100)->nullable()->unique()->after('id');
            $table->string('source_url', 500)->nullable()->after('sahaba_id');
        });
    }

    public function down(): void
    {
        Schema::table('hadith_scholars', function (Blueprint $table): void {
            $table->dropUnique(['external_id']);
            $table->dropColumn(['external_id', 'source_url']);
        });
    }
};
