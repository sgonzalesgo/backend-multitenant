<?php

namespace App\Models\Academic;

use App\Models\Administration\Tenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AcademicSchedule extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'academic_schedules';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'tenant_id',
        'academic_year_id',
        'course_id',
        'parallel_id',
        'modality_id',
        'shift_id',
        'status',
        'general_observation',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function parallel(): BelongsTo
    {
        return $this->belongsTo(Parallel::class);
    }

    public function modality(): BelongsTo
    {
        return $this->belongsTo(Modality::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function frequencies(): HasMany
    {
        return $this->hasMany(AcademicScheduleFrequency::class);
    }
}
