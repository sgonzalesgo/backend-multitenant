<?php

namespace App\Models\Academic;

use App\Models\Calendar\CalendarEvent;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AcademicScheduleFrequency extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'academic_schedule_frequencies';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'academic_schedule_id',
        'day_of_week',
        'start_time',
        'end_time',
        'classroom_id',
        'subject_id',
        'instructor_id',
        'calendar_event_id',
        'observation',
    ];

    protected $casts = [
        'day_of_week' => 'integer',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
    ];

    public function academicSchedule(): BelongsTo
    {
        return $this->belongsTo(AcademicSchedule::class);
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(Instructor::class);
    }

    public function calendarEvent(): BelongsTo
    {
        return $this->belongsTo(CalendarEvent::class);
    }
}
