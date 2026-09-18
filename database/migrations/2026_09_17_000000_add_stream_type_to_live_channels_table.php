<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('live_channels', function (Blueprint $table) {
            // Nullable on purpose: null means "read it off the streaming URL"
            // (StreamType::detect), which is right for every existing row.
            $table->string('stream_type', 20)->nullable()->after('streaming_url');
        });
    }

    public function down(): void
    {
        Schema::table('live_channels', function (Blueprint $table) {
            $table->dropColumn('stream_type');
        });
    }
};
