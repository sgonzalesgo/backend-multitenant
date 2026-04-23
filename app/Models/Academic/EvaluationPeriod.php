<?php

namespace App\Models\Academic;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

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
    ];

    protected $casts = [
        'default_order' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    protected $appends = [
        'is_active',
    ];

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function getIsActiveAttribute(): bool
    {
        $today = Carbon::today();

        return $this->start_date !== null
            && $this->end_date !== null
            && $this->start_date->lte($today)
            && $this->end_date->gte($today);
    }

    public function scopeActive(Builder $query): Builder
    {
        $today = Carbon::today()->toDateString();

        return $query
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today);
    }

    public function scopeInactive(Builder $query): Builder
    {
        $today = Carbon::today()->toDateString();

        return $query->where(function ($q) use ($today) {
            $q->whereDate('start_date', '>', $today)
                ->orWhereDate('end_date', '<', $today);
        });
    }
}
