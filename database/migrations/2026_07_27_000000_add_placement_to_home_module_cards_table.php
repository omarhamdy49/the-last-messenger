<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('home_module_cards', function (Blueprint $table): void {
            $table->string('placement')->default('explore')->after('key')->index();
        });
    }

    public function down(): void
    {
        Schema::table('home_module_cards', function (Blueprint $table): void {
            $table->dropIndex(['placement']);
            $table->dropColumn('placement');
        });
    }
};
