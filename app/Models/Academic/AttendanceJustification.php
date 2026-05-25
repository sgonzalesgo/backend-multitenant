<?php

namespace App\Models\Academic;

use App\Models\Administration\Tenant;
use App\Models\Administration\User;
use App\Models\General\Person;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AttendanceJustification extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'attendance_justifications';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'tenant_id',
        'attendance_record_id',
        'attendance_session_id',
        'student_id',
        'person_id',
        'requested_by',
        'reviewed_by',
        'justification_type',
        'reason',
        'document_path',
        'status',
        'reviewed_at',
        'review_observation',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function attendanceRecord(): BelongsTo
    {
        return $this->belongsTo(AttendanceRecord::class);
    }

    public function attendanceSession(): BelongsTo
    {
        return $this->belongsTo(AttendanceSession::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
