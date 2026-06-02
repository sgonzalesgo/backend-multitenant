<?php

namespace App\Models\Academic;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QualitativeEvaluationArea extends Model
{
    use HasUuids;

    protected $table = 'qualitative_evaluation_areas';

    protected $fillable = [
        'tenant_id',
        'code',
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function skills(): HasMany
    {
        return $this->hasMany(QualitativeSkillDefinition::class, 'qualitative_evaluation_area_id');
    }
}
