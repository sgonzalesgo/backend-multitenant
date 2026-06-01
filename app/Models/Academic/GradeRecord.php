<?php

namespace App\Models\Academic;

use App\Models\Administration\Tenant;
use App\Models\General\Person;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GradeRecord extends Model
{
    use HasUuids;

    protected $table = 'grade_records';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'tenant_id',
        'grade_session_id',
        'enrollment_id',
        'student_id',
        'person_id',
        'final_score',
        'final_status',
        'qualitative_grade',
        'observation',
    ];

    protected $casts = [
        'final_score' => 'decimal:2',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function gradeSession(): BelongsTo
    {
        return $this->belongsTo(GradeSession::class);
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

    public function components(): HasMany
    {
        return $this->hasMany(GradeRecordComponent::class);
    }
}
