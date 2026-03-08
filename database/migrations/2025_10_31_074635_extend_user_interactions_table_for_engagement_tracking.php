<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        // Update user_interactions table to support more interaction types
        if ($driver !== 'sqlite') {
            Schema::table('user_interactions', function (Blueprint $table) {
                if (Schema::hasColumn('user_interactions', 'interaction_type')) {
                    $table->dropColumn('interaction_type');
                }
            });

            Schema::table('user_interactions', function (Blueprint $table) {
                $table->enum('interaction_type', [
                    'view',
                    'like',
                    'share',
                    'bookmark',
                    'comment',
                    'download',
                    'print',
                    'search',
                    'progress',
                    'complete',
                ])->default('view')->after('interactable_type');
            });
        }

        Schema::table('user_interactions', function (Blueprint $table) {
            if (! Schema::hasColumn('user_interactions', 'duration_seconds')) {
                $table->integer('duration_seconds')->nullable()->after('metadata');
            }
            if (! Schema::hasColumn('user_interactions', 'ip_address')) {
                $table->ipAddress('ip_address')->nullable()->after('duration_seconds');
            }
            if (! Schema::hasColumn('user_interactions', 'user_agent')) {
                $table->string('user_agent', 500)->nullable()->after('ip_address');
            }
            if (! Schema::hasColumn('user_interactions', 'device_type')) {
                $table->string('device_type', 50)->nullable()->after('user_agent');
            }
            if (! Schema::hasColumn('user_interactions', 'platform')) {
                $table->string('platform', 50)->nullable()->after('device_type');
            }
        });

        // Add additional indexes for better query performance
        Schema::table('user_interactions', function (Blueprint $table) {
            $table->index('created_at', 'ui_created_at_idx'); // For time-based queries
            $table->index(['user_id', 'created_at'], 'ui_user_created_idx'); // User activity timeline
            $table->index(['interactable_type', 'interactable_id', 'interaction_type'], 'ui_content_type_idx'); // Content engagement stats
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_interactions', function (Blueprint $table) {
            $table->dropIndex('ui_created_at_idx');
            $table->dropIndex('ui_user_created_idx');
            $table->dropIndex('ui_content_type_idx');
            $table->dropColumn([
                'duration_seconds',
                'ip_address',
                'user_agent',
                'device_type',
                'platform',
            ]);
        });

        // Restore original enum
        Schema::table('user_interactions', function (Blueprint $table) {
            $table->dropColumn('interaction_type');
        });

        Schema::table('user_interactions', function (Blueprint $table) {
            $table->enum('interaction_type', ['view', 'progress', 'complete'])->default('view')->after('interactable_type');
        });
    }
};
