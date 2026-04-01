<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_events', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('tenant_id');
            $table->uuid('event_type_id')->nullable();

            $table->uuid('created_by');
            $table->uuid('updated_by')->nullable();

            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->string('location', 255)->nullable();
            $table->string('url', 500)->nullable();

            $table->timestampTz('start_at');
            $table->timestampTz('end_at')->nullable();
            $table->boolean('all_day')->default(false);
            $table->string('timezone', 100)->default('America/New_York');

            $table->string('status', 30)->default('confirmed'); // draft, confirmed, cancelled
            $table->string('visibility', 30)->default('restricted'); // private, restricted, public_tenant
            $table->string('source', 30)->default('internal'); // internal, google, synced
            $table->string('editable_by', 30)->default('creator_only'); // creator_only, admins, system

            $table->string('color', 20)->nullable();

            $table->boolean('is_recurring')->default(false);
            $table->text('recurrence_rule')->nullable();

            $table->boolean('google_sync_enabled')->default(false);
            $table->timestampTz('google_last_synced_at')->nullable();

            $table->jsonb('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id');
            $table->index('event_type_id');
            $table->index('created_by');
            $table->index('start_at');
            $table->index('end_at');
            $table->index('status');
            $table->index('visibility');
            $table->index('source');
            $table->index('google_sync_enabled');

            $table->foreign('event_type_id')
                ->references('id')
                ->on('calendar_event_types')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_events');
    }
};
