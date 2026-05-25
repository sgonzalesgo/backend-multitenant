<?php

namespace App\Models\Academic;

use App\Models\Administration\Tenant;
use App\Models\General\Person;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRecord extends Model
{
    use HasUuids;

    protected $table = 'attendance_records';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'tenant_id',
        'attendance_session_id',
        'enrollment_id',
        'student_id',
        'person_id',
        'status',
        'late_minutes',
        'observation',
        'absence_notified_at',
        'requires_justification',
        'justification_status',
        'justified_at',
    ];

    protected $casts = [
        'late_minutes' => 'integer',
        'absence_notified_at' => 'datetime',
        'requires_justification' => 'boolean',
        'justified_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function attendanceSession(): BelongsTo
    {
        return $this->belongsTo(AttendanceSession::class);
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function justification(): HasOne
    {
        return $this->hasOne(
            AttendanceJustification::class,
            'attendance_record_id'
        );
    }
}
