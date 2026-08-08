<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Extended biographical detail, as one JSON column.
 *
 * WHY JSON AND NOT COLUMNS: `muslims` already stores kunya, bio, content and
 * birth_place as JSON, so this is the house style for this table. The
 * alternative — eighteen flat columns for tribe, death place, Gregorian years
 * and the prophetic-mission fields — would be null for almost every one of the
 * imported people, and every future biographical field would need another
 * migration.
 *
 * WHY IT IS SAFE TO BE SCHEMALESS: the shape is not free-form. It is defined
 * once in App\Domain\People\ValueObjects\MuslimProfile::fields(), which is the
 * single source the admin form, the validation rules and the read accessors are
 * all generated from. Adding a field there makes it appear in the form, be
 * validated, and be readable — with no migration. A key not in that registry is
 * dropped on write, so a typo cannot quietly invent a field.
 *
 * The 002 rebuild dropped tribe / death place / Gregorian dates as Owner
 * Decisions 2, 4 and 6 because most people carry none of them. This does not
 * reverse that: the lean identity columns are untouched, and a person with no
 * extended detail stores NULL here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('muslims', function (Blueprint $table): void {
            $table->json('profile')->nullable()->after('generation');
        });
    }

    public function down(): void
    {
        Schema::table('muslims', function (Blueprint $table): void {
            $table->dropColumn('profile');
        });
    }
};
