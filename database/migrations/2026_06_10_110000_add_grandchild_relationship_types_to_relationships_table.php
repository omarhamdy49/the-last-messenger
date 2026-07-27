<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The Genealogy domain models (RelationshipType enum + Relationship::getInverseRelationshipType)
 * reference `grandson` and `granddaughter` relationship types, but the original
 * relationships table enum was created without them. Widen the enum so the
 * Prophet's ﷺ grandchildren can be linked to their grandparents directly,
 * making the family graph traversable across generations.
 */
return new class extends Migration
{
    private const RELATIONSHIP_TYPES = [
        'father',
        'mother',
        'son',
        'daughter',
        'brother',
        'sister',
        'husband',
        'wife',
        'grandfather',
        'grandmother',
        'grandson',
        'granddaughter',
        'uncle',
        'aunt',
        'nephew',
        'niece',
        'cousin',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            $values = "'".implode("','", self::RELATIONSHIP_TYPES)."'";
            DB::statement("ALTER TABLE relationships MODIFY relationship_type ENUM({$values}) NOT NULL");
        } else {
            // Non-MySQL drivers (tests) keep the column permissive as a plain string.
            Schema::table('relationships', function (Blueprint $table) {
                $table->string('relationship_type', 30)->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rows using the new types cannot survive the narrowing — remove them.
        DB::table('relationships')->whereIn('relationship_type', ['grandson', 'granddaughter'])->delete();

        if (DB::getDriverName() === 'mysql') {
            $originalTypes = array_values(array_diff(self::RELATIONSHIP_TYPES, ['grandson', 'granddaughter']));
            $values = "'".implode("','", $originalTypes)."'";
            DB::statement("ALTER TABLE relationships MODIFY relationship_type ENUM({$values}) NOT NULL");
        }
    }
};
