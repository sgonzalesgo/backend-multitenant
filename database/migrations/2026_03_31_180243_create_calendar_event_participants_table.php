<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_event_participants', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('tenant_id');
            $table->uuid('calendar_event_id');

            $table->uuid('user_id')->nullable();
            $table->uuid('person_id')->nullable();

            $table->string('participant_type', 30); // user, student, teacher, parent, external
            $table->string('role', 30)->default('attendee'); // owner, organizer, attendee, viewer
            $table->string('response_status', 30)->default('pending'); // pending, accepted, declined, tentative

            $table->boolean('is_required')->default(false);
            $table->boolean('can_view')->default(true);
            $table->boolean('can_receive_notifications')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id');
            $table->index('calendar_event_id');
            $table->index('user_id');
            $table->index('person_id');
            $table->index('participant_type');
            $table->index('role');
            $table->index('response_status');

            $table->foreign('calendar_event_id')
                ->references('id')
                ->on('calendar_events')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_event_participants');
    }
};
