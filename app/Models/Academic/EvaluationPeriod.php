<?php

namespace App\Models\Academic;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EvaluationPeriod extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'evaluation_periods';

    protected $fillable = [
        'code',
        'name',
        'description',
        'default_order',
        'is_active',
    ];

    protected $casts = [
        'default_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function academicYearEvaluationPeriods(): HasMany
    {
        return $this->hasMany(AcademicYearEvaluationPeriod::class);
    }
}
