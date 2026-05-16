<?php

namespace App\Models\Academic;

use App\Models\Administration\Tenant;
use App\Models\Administration\User;
use App\Models\Calendar\CalendarEvent;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AttendanceSession extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'attendance_sessions';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'tenant_id',
        'calendar_event_id',
        'academic_schedule_id',
        'academic_schedule_frequency_id',
        'academic_year_id',
        'course_id',
        'parallel_id',
        'subject_id',
        'instructor_id',
        'attendance_date',
        'status',
        'observation',
        'created_by',
        'closed_at',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'closed_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function calendarEvent(): BelongsTo
    {
        return $this->belongsTo(CalendarEvent::class);
    }

    public function academicSchedule(): BelongsTo
    {
        return $this->belongsTo(AcademicSchedule::class);
    }

    public function academicScheduleFrequency(): BelongsTo
    {
        return $this->belongsTo(AcademicScheduleFrequency::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function parallel(): BelongsTo
    {
        return $this->belongsTo(Parallel::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(Instructor::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function records(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }
}
