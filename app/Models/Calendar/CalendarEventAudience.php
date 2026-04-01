<?php

namespace App\Models\Calendar;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Administration\Tenant;

class CalendarEventAudience extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'calendar_event_audiences';

    protected $fillable = [
        'tenant_id',
        'calendar_event_id',
        'audience_type',
        'audience_id',
        'filters',
    ];

    protected $casts = [
        'filters' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(CalendarEvent::class, 'calendar_event_id');
    }
}
