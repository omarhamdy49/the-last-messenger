<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * تنبيه الخلاف في تأريخ الحدث.
 *
 * التسلسل الزمني المرجعي (seerah-unified-timeline.json) يحمل لـ26 حدثًا نصَّ
 * الخلاف في تأريخها — «موافقة عام الفيل لسنة 570/571م هي المشهور، وقد رجّح
 * باحثون سنة 571م» — ولم يكن له حقل، فكان يُفقد كلَّ مرة.
 *
 * مترجَم لأن نصَّه نصٌّ منظور، لا وسم.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seerah_event_translations', function (Blueprint $table) {
            $table->text('dispute_note')->nullable()->after('sources');
        });
    }

    public function down(): void
    {
        Schema::table('seerah_event_translations', function (Blueprint $table) {
            $table->dropColumn('dispute_note');
        });
    }
};
