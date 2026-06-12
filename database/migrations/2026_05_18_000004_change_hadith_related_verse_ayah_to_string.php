<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hadith_related_verses', function (Blueprint $table) {
            $table->string('ayah', 50)->change();
        });
    }

    public function down(): void
    {
        Schema::table('hadith_related_verses', function (Blueprint $table) {
            $table->integer('ayah')->change();
        });
    }
};
