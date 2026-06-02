<?php

namespace App\Models\Academic;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QualitativeEvaluationComponent extends Model
{
    use HasUuids;

    protected $table = 'qualitative_evaluation_components';

    protected $fillable = [
        'tenant_id',
        'academic_year_id',
        'evaluation_period_id',
        'course_id',
        'parallel_id',
        'modality_id',
        'shift_id',
        'subject_id',
        'qualitative_evaluation_template_id',
        'qualitative_skill_definition_id',
        'order',
        'is_required',
        'is_active',
    ];

    protected $casts = [
        'order' => 'integer',
        'is_required' => 'boolean',
        'is_active' => 'boolean',
    ];

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

    public function template(): BelongsTo
    {
        return $this->belongsTo(
            QualitativeEvaluationTemplate::class,
            'qualitative_evaluation_template_id'
        );
    }

    public function skillDefinition(): BelongsTo
    {
        return $this->belongsTo(
            QualitativeSkillDefinition::class,
            'qualitative_skill_definition_id'
        );
    }
}
