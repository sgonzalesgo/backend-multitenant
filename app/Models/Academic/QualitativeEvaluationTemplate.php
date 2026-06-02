<?php

namespace App\Models\Academic;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QualitativeEvaluationTemplate extends Model
{
    use HasUuids;

    protected $table = 'qualitative_evaluation_templates';

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'educational_level_id',
        'course_id',
        'evaluation_period_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function educationalLevel(): BelongsTo
    {
        return $this->belongsTo(EducationalLevel::class, 'educational_level_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function evaluationPeriod(): BelongsTo
    {
        return $this->belongsTo(EvaluationPeriod::class, 'evaluation_period_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(
            QualitativeEvaluationTemplateItem::class,
            'qualitative_evaluation_template_id'
        )->orderBy('default_order');
    }

    public function components(): HasMany
    {
        return $this->hasMany(
            QualitativeEvaluationComponent::class,
            'qualitative_evaluation_template_id'
        );
    }
}
