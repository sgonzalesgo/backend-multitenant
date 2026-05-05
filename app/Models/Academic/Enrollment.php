<?php

namespace App\Models\Academic;

use App\Models\Administration\Tenant;
use App\Models\Administration\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Enrollment extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'enrollments';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'tenant_id',
        'enrollment_code',
        'student_id',
        'academic_year_id',
        'course_id',
        'parallel_id',
        'shift_id',
        'enrollment_status_id',
        'assigned_user_id',
        'is_new',
        'is_conditional',
        'is_active',
        'observation',
        'submitted_at',
        'student_email_sent_at',
        'representatives_email_sent_at',
    ];

    protected $casts = [
        'is_new' => 'boolean',
        'is_conditional' => 'boolean',
        'is_active' => 'boolean',
        'submitted_at' => 'datetime',
        'student_email_sent_at' => 'datetime',
        'representatives_email_sent_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
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

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function enrollmentStatus(): BelongsTo
    {
        return $this->belongsTo(EnrollmentStatus::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }
}
