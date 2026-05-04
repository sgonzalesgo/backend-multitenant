<?php

namespace App\Models\Academic;

use App\Models\Administration\Tenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Course extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'courses';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'tenant_id',
        'educational_level_id',
        'instructor_id',
        'code',
        'name',
        'description',
        'capacity',
        'credits',
        'theoretical_hours',
        'practical_hours',
        'total_hours',
        'status',
        'notes',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'credits' => 'integer',
        'theoretical_hours' => 'integer',
        'practical_hours' => 'integer',
        'total_hours' => 'integer',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function educationalLevel(): BelongsTo
    {
        return $this->belongsTo(EducationalLevel::class);
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(Instructor::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
