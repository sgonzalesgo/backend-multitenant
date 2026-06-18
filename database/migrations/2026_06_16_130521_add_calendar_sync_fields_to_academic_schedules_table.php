<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('academic_schedules', function (Blueprint $table) {
            $table->string('calendar_sync_status', 40)
                ->default('pending')
                ->after('general_observation');

            $table->text('calendar_sync_error')
                ->nullable()
                ->after('calendar_sync_status');

            $table->timestamp('calendar_sync_requested_at')
                ->nullable()
                ->after('calendar_sync_error');

            $table->timestamp('calendar_synced_at')
                ->nullable()
                ->after('calendar_sync_requested_at');

            $table->index(['tenant_id', 'calendar_sync_status']);

            $table->unsignedInteger('calendar_sync_total_events')
                ->default(0)
                ->after('calendar_synced_at');

            $table->unsignedInteger('calendar_sync_processed_events')
                ->default(0)
                ->after('calendar_sync_total_events');

            $table->unsignedTinyInteger('calendar_sync_progress')
                ->default(0)
                ->after('calendar_sync_processed_events');
        });
    }

    public function down(): void
    {
        Schema::table('academic_schedules', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'calendar_sync_status']);

            $table->dropColumn([
                'calendar_sync_status',
                'calendar_sync_error',
                'calendar_sync_requested_at',
                'calendar_synced_at',
                'calendar_sync_total_events',
                'calendar_sync_processed_events',
                'calendar_sync_progress',
            ]);
        });
    }
};
