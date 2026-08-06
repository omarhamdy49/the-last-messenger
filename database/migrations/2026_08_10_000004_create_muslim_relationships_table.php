<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * data-model.md §4 — only the edges descent cannot express.
 *
 * Parent, child, and grandparent are derived from `muslims.father_id` /
 * `mother_id` and are never stored here.
 *
 * Every type is symmetric, so a relationship is stored ONCE and read with
 * `muslim_id = ? OR related_muslim_id = ?`.
 *
 * Note what the unique index can and cannot do: it catches an exact duplicate,
 * but (A,B,sibling) and (B,A,sibling) are distinct rows to MySQL. The
 * repository normalises pair order on write so the index can actually catch
 * that case — constraint plus service, because the constraint alone is
 * insufficient.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('muslim_relationships', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('muslim_id')->constrained('muslims')->cascadeOnDelete();
            $table->foreignId('related_muslim_id')->constrained('muslims')->cascadeOnDelete();
            $table->string('relationship_type', 20);
            $table->text('notes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('hide_from_tree')->default(false);
            $table->timestamps();

            $table->unique(
                ['muslim_id', 'related_muslim_id', 'relationship_type'],
                'uq_muslim_relationships'
            );

            $table->index(['muslim_id', 'relationship_type']);
            // The far side of the OR — reading a relationship stored in the
            // other direction.
            $table->index('related_muslim_id', 'muslim_relationships_related_idx');
        });

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement(
                'ALTER TABLE muslim_relationships ADD CONSTRAINT chk_muslim_relationships_not_self '.
                'CHECK (muslim_id <> related_muslim_id)'
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('muslim_relationships');
    }
};
