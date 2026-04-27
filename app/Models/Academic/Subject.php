<?php

namespace App\Models\Academic;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subject extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'subjects';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'tenant_id',
        'subject_type_id',
        'evaluation_type_id',
        'code',
        'name',
        'description',
        'is_average',
        'is_behavior',
        'is_active',
    ];

    protected $casts = [
        'is_average' => 'boolean',
        'is_behavior' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function subjectType(): BelongsTo
    {
        return $this->belongsTo(SubjectType::class);
    }

    public function evaluationType(): BelongsTo
    {
        return $this->belongsTo(EvaluationType::class);
    }
}
