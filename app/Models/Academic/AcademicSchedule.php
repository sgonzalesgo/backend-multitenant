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
        'specialty_id',
        'parallel_id',
        'modality_id',
        'shift_id',
        'status',
        'general_observation',
        'calendar_sync_status',
        'calendar_sync_error',
        'calendar_sync_requested_at',
        'calendar_synced_at',
        'calendar_sync_total_events',
        'calendar_sync_processed_events',
        'calendar_sync_progress',
    ];

    protected $casts = [
        'calendar_sync_requested_at' => 'datetime',
        'calendar_synced_at' => 'datetime',
        'calendar_sync_total_events' => 'integer',
        'calendar_sync_processed_events' => 'integer',
        'calendar_sync_progress' => 'integer',
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

    public function specialty(): BelongsTo
    {
        return $this->belongsTo(Specialty::class);
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
