<?php

namespace App\Models\Academic;

use App\Models\Administration\Tenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class GradeComponentTemplateItem extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'grade_component_template_items';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'tenant_id',
        'grade_component_template_id',
        'grade_component_definition_id',
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

    public function template(): BelongsTo
    {
        return $this->belongsTo(
            GradeComponentTemplate::class,
            'grade_component_template_id'
        );
    }

    public function definition(): BelongsTo
    {
        return $this->belongsTo(
            GradeComponentDefinition::class,
            'grade_component_definition_id'
        );
    }
}
