<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hadith_collections', function (Blueprint $table): void {
            $table->json('introduction')->nullable()->after('about');
            $table->string('introduction_url', 500)->nullable()->after('about_url');
        });
    }

    public function down(): void
    {
        Schema::table('hadith_collections', function (Blueprint $table): void {
            $table->dropColumn(['introduction', 'introduction_url']);
        });
    }
};
