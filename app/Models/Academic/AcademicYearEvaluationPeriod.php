<?php

namespace App\Models\Academic;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcademicYearEvaluationPeriod extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'academic_year_evaluation_periods';

    protected $fillable = [
        'academic_year_id',
        'evaluation_period_id',
        'order',
        'start_date',
        'end_date',
        'is_active',
    ];

    protected $casts = [
        'order' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Academic Year
     */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /**
     * Evaluation Period (catalog)
     */
    public function evaluationPeriod(): BelongsTo
    {
        return $this->belongsTo(EvaluationPeriod::class);
    }
}
