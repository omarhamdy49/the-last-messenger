<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/*
 * taggables rows written before the morph map existed store the FQCN
 * (App\Models\Article); Eloquent now reads/writes the 'article' alias,
 * making those rows invisible to every tag relation. Normalize to the alias.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('taggables')
            ->where('taggable_type', 'App\\Models\\Article')
            ->update(['taggable_type' => 'article']);
    }

    public function down(): void
    {
        DB::table('taggables')
            ->where('taggable_type', 'article')
            ->update(['taggable_type' => 'App\\Models\\Article']);
    }
};
