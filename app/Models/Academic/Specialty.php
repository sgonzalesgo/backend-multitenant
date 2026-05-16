<?php

namespace App\Models\Academic;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Specialty extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'specialties';

    protected $keyType = 'string';

    public $incrementing = false;

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

    public function educationalLevels(): BelongsToMany
    {
        return $this->belongsToMany(
            EducationalLevel::class,
            'educational_level_specialty',
            'specialty_id',
            'educational_level_id'
        )
            ->withPivot('tenant_id')
            ->withTimestamps();
    }
}
