<?php

namespace App\Models\Academic;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvaluationPeriod extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'evaluation_periods';

    protected $fillable = [
        'academic_year_id',
        'code',
        'name',
        'description',
        'default_order',
        'start_date',
        'end_date',
        'is_active',
    ];

    protected $casts = [
        'default_order' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }
}
