<?php

namespace App\Models\Academic;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QualitativeEvaluationRecordSkill extends Model
{
    use HasUuids;

    protected $table = 'qualitative_evaluation_record_skills';

    protected $fillable = [
        'tenant_id',
        'qualitative_evaluation_record_id',
        'qualitative_evaluation_component_id',
        'value',
        'observation',
    ];

    public function record(): BelongsTo
    {
        return $this->belongsTo(
            QualitativeEvaluationRecord::class,
            'qualitative_evaluation_record_id'
        );
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(
            QualitativeEvaluationComponent::class,
            'qualitative_evaluation_component_id'
        );
    }
}
