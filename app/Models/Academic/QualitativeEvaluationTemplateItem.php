<?php

namespace App\Models\Academic;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QualitativeEvaluationTemplateItem extends Model
{
    use HasUuids;

    protected $table = 'qualitative_evaluation_template_items';

    protected $fillable = [
        'tenant_id',
        'qualitative_evaluation_template_id',
        'qualitative_skill_definition_id',
        'default_order',
        'is_required',
    ];

    protected $casts = [
        'default_order' => 'integer',
        'is_required' => 'boolean',
    ];

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
