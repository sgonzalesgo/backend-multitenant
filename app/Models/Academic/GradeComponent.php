<?php

namespace App\Models\Academic;

use App\Models\Administration\Tenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class GradeComponent extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'grade_components';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'tenant_id',
        'academic_year_id',
        'evaluation_period_id',
        'course_id',
        'parallel_id',
        'specialty_id',
        'modality_id',
        'shift_id',
        'subject_id',
        'evaluation_type_id',
        'component_key',
        'component_type',
        'code',
        'name',
        'description',
        'weight',
        'max_score',
        'default_order',
        'is_required',
        'is_system_calculated',
        'is_active',
        'settings',
    ];

    protected $casts = [
        'weight' => 'decimal:2',
        'max_score' => 'decimal:2',
        'default_order' => 'integer',
        'is_required' => 'boolean',
        'is_system_calculated' => 'boolean',
        'is_active' => 'boolean',
        'settings' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function evaluationPeriod(): BelongsTo
    {
        return $this->belongsTo(EvaluationPeriod::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function specialty(): BelongsTo
    {
        return $this->belongsTo(Specialty::class);
    }

    public function modality(): BelongsTo
    {
        return $this->belongsTo(Modality::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function evaluationType(): BelongsTo
    {
        return $this->belongsTo(EvaluationType::class);
    }

    public function recordComponents(): HasMany
    {
        return $this->hasMany(GradeRecordComponent::class);
    }

    public function parallel(): BelongsTo
    {
        return $this->belongsTo(Parallel::class);
    }
}
