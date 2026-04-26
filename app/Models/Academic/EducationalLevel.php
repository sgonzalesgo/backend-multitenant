<?php

namespace App\Models\Academic;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EducationalLevel extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'educational_levels';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'tenant_id',
        'code',
        'name',
        'sort_order',
        'start_number',
        'end_number',
        'has_specialty',
        'next_educational_level_id',
        'description',
        'is_active',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'start_number' => 'integer',
        'end_number' => 'integer',
        'has_specialty' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function nextEducationalLevel(): BelongsTo
    {
        return $this->belongsTo(
            EducationalLevel::class,
            'next_educational_level_id'
        );
    }

    public function previousEducationalLevels(): HasMany
    {
        return $this->hasMany(
            EducationalLevel::class,
            'next_educational_level_id'
        );
    }
}
