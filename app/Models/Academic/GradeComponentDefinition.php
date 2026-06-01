<?php

namespace App\Models\Academic;

use App\Models\Administration\Tenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class GradeComponentDefinition extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'grade_component_definitions';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'tenant_id',
        'component_key',
        'component_type',
        'code',
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function templateItems(): HasMany
    {
        return $this->hasMany(
            GradeComponentTemplateItem::class,
            'grade_component_definition_id'
        );
    }

    public function isNumeric(): bool
    {
        return $this->component_type === 'numeric';
    }

    public function isBehavior(): bool
    {
        return $this->component_type === 'behavior';
    }

    public function isQualitative(): bool
    {
        return $this->component_type === 'qualitative';
    }
}
