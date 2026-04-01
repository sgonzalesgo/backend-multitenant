<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_external_mappings', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('tenant_id');
            $table->uuid('calendar_event_id');
            $table->uuid('external_account_id');

            $table->string('provider', 30); // google
            $table->string('external_calendar_id', 255)->nullable();
            $table->string('external_event_id', 255);
            $table->string('external_etag', 255)->nullable();

            $table->string('sync_direction', 30)->default('push'); // push, pull, bidirectional
            $table->string('sync_status', 30)->default('synced'); // pending, synced, failed
            $table->text('sync_error')->nullable();

            $table->timestampTz('last_synced_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id');
            $table->index('calendar_event_id');
            $table->index('external_account_id');
            $table->index('provider');
            $table->index('external_event_id');
            $table->index('sync_status');

            $table->foreign('calendar_event_id')
                ->references('id')
                ->on('calendar_events')
                ->cascadeOnDelete();

            $table->foreign('external_account_id')
                ->references('id')
                ->on('calendar_external_accounts')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_external_mappings');
    }
};
