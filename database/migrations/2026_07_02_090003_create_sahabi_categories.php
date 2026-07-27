<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Replace the single-value `status` enum with a lookup + pivot so a Sahabi
 * can carry more than one historical-rank category at once — a Muhajir who
 * fought at Badr is both, but the old enum could only ever store one.
 * Mirrors the `sahabi_battles` pattern already used for the same problem
 * on battle participation.
 */
return new class extends Migration
{
    private const CATEGORY_RANKS = [
        'ten_promised' => 10,
        'badr' => 9,
        'early_muslim' => 8,
        'muhajir' => 7,
        'uhud' => 6,
        'ansar' => 5,
        'tabi' => 3,
    ];

    private const STATUS_TO_CATEGORY = [
        'ten_promised_paradise' => 'ten_promised',
        'badr_participant' => 'badr',
        'uhud_participant' => 'uhud',
        'early_muslim' => 'early_muslim',
        'muhajir' => 'muhajir',
        'ansar' => 'ansar',
        'tabi' => 'tabi',
    ];

    public function up(): void
    {
        Schema::create('sahabi_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 30)->unique();
            $table->unsignedTinyInteger('rank')->default(0);
            $table->timestamps();
        });

        Schema::create('sahabi_category', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sahabi_id')->constrained('sahaba')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('sahabi_categories')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['sahabi_id', 'category_id']);
            // Primary access pattern is browsing/filtering by category, then
            // joining to sahaba — leading column matches the WHERE clause.
            $table->index(['category_id', 'sahabi_id'], 'sahabi_category_cat_sahabi_idx');
        });

        $categoryIds = [];
        foreach (self::CATEGORY_RANKS as $key => $rank) {
            $categoryIds[$key] = DB::table('sahabi_categories')->insertGetId([
                'key' => $key,
                'rank' => $rank,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        foreach (self::STATUS_TO_CATEGORY as $status => $categoryKey) {
            $sahabaIds = DB::table('sahaba')->where('status', $status)->pluck('id');
            $rows = $sahabaIds->map(fn ($sahabiId) => [
                'sahabi_id' => $sahabiId,
                'category_id' => $categoryIds[$categoryKey],
                'created_at' => now(),
                'updated_at' => now(),
            ])->all();

            foreach (array_chunk($rows, 1000) as $chunk) {
                DB::table('sahabi_category')->insert($chunk);
            }
        }

        Schema::table('sahaba', function (Blueprint $table): void {
            $table->dropIndex(['status']);
            $table->dropColumn('status');
        });
    }

    public function down(): void
    {
        Schema::table('sahaba', function (Blueprint $table): void {
            $table->string('status', 30)->default('prophet_companion')->after('gender');
        });

        $categoryToStatus = array_flip(self::STATUS_TO_CATEGORY);

        foreach (DB::table('sahabi_category')
            ->join('sahabi_categories', 'sahabi_categories.id', '=', 'sahabi_category.category_id')
            ->select('sahabi_category.sahabi_id', 'sahabi_categories.key')
            ->orderByDesc('sahabi_categories.rank')
            ->get() as $row) {
            $status = $categoryToStatus[$row->key] ?? null;
            if ($status === null) {
                continue;
            }

            // Highest-rank category wins when a Sahabi holds several —
            // matches the pre-pivot single-value semantics as closely as possible.
            DB::table('sahaba')
                ->where('id', $row->sahabi_id)
                ->where('status', 'prophet_companion')
                ->update(['status' => $status]);
        }

        Schema::dropIfExists('sahabi_category');
        Schema::dropIfExists('sahabi_categories');
    }
};
