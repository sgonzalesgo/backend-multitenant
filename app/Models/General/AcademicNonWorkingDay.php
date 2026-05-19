<?php

namespace App\Models\General;

use App\Models\Academic\AcademicYear;
use App\Models\Administration\Tenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AcademicNonWorkingDay extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'academic_non_working_days';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'tenant_id',
        'academic_year_id',
        'date',
        'name',
        'type',
        'affects_attendance',
        'affects_calendar',
        'is_active',
        'observation',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'affects_attendance' => 'boolean',
        'affects_calendar' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }
}
