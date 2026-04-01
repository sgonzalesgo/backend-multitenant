<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_event_audiences', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('tenant_id');
            $table->uuid('calendar_event_id');

            $table->string('audience_type', 30); // tenant, role, course, section, grade, department, user, student, teacher, parent
            $table->uuid('audience_id')->nullable();

            $table->jsonb('filters')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id');
            $table->index('calendar_event_id');
            $table->index('audience_type');
            $table->index('audience_id');

            $table->foreign('calendar_event_id')
                ->references('id')
                ->on('calendar_events')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_event_audiences');
    }
};
