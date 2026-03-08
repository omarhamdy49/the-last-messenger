<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media', function (Blueprint $table): void {
            $table->char('public_key', 26)->nullable()->unique()->after('id');
        });

        DB::table('media')
            ->select('id')
            ->orderBy('id')
            ->chunkById(200, function ($items): void {
                foreach ($items as $item) {
                    DB::table('media')
                        ->where('id', $item->id)
                        ->update(['public_key' => (string) Str::ulid()]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('media', function (Blueprint $table): void {
            $table->dropUnique(['public_key']);
            $table->dropColumn('public_key');
        });
    }
};
