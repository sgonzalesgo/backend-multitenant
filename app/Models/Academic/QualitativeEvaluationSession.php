<?php

namespace App\Models\Academic;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QualitativeEvaluationSession extends Model
{
    use HasUuids;

    protected $table = 'qualitative_evaluation_sessions';

    protected $fillable = [
        'tenant_id',
        'academic_year_id',
        'evaluation_period_id',
        'course_id',
        'specialty_id',
        'parallel_id',
        'modality_id',
        'shift_id',
        'subject_id',
        'name',
        'is_closed',
    ];

    protected $casts = [
        'is_closed' => 'boolean',
    ];

    public function records(): HasMany
    {
        return $this->hasMany(QualitativeEvaluationRecord::class, 'qualitative_evaluation_session_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    public function evaluationPeriod(): BelongsTo
    {
        return $this->belongsTo(EvaluationPeriod::class, 'evaluation_period_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function specialty(): BelongsTo
    {
        return $this->belongsTo(Specialty::class, 'specialty_id');
    }

    public function parallel(): BelongsTo
    {
        return $this->belongsTo(Parallel::class, 'parallel_id');
    }

    public function modality(): BelongsTo
    {
        return $this->belongsTo(Modality::class, 'modality_id');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'shift_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }
}
