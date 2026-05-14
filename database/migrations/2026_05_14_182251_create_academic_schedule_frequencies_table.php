<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_schedule_frequencies', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('academic_schedule_id')
                ->constrained('academic_schedules')
                ->cascadeOnDelete();

            $table->unsignedTinyInteger('day_of_week');

            $table->time('start_time');
            $table->time('end_time');

            $table->foreignUuid('classroom_id')
                ->constrained('classrooms')
                ->cascadeOnDelete();

            $table->foreignUuid('subject_id')
                ->constrained('subjects')
                ->cascadeOnDelete();

            $table->foreignUuid('instructor_id')
                ->constrained('instructors')
                ->cascadeOnDelete();

            $table->uuid('calendar_event_id')->nullable();

            $table->text('observation')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['academic_schedule_id', 'day_of_week']);
            $table->index(['classroom_id', 'day_of_week', 'start_time', 'end_time']);
            $table->index(['instructor_id', 'day_of_week', 'start_time', 'end_time']);
            $table->index(['subject_id']);
            $table->index(['calendar_event_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_schedule_frequencies');
    }
};
