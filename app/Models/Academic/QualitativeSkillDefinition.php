<?php

namespace App\Models\Academic;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QualitativeSkillDefinition extends Model
{
    use HasUuids;

    protected $table = 'qualitative_skill_definitions';

    protected $fillable = [
        'tenant_id',
        'qualitative_evaluation_area_id',
        'code',
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function area(): BelongsTo
    {
        return $this->belongsTo(
            QualitativeEvaluationArea::class,
            'qualitative_evaluation_area_id'
        );
    }

    public function templateItems(): HasMany
    {
        return $this->hasMany(
            QualitativeEvaluationTemplateItem::class,
            'qualitative_skill_definition_id'
        );
    }

    public function components(): HasMany
    {
        return $this->hasMany(
            QualitativeEvaluationComponent::class,
            'qualitative_skill_definition_id'
        );
    }
}
