<?php

namespace App\Models\Calendar;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Administration\Tenant;

class CalendarExternalMapping extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'calendar_external_mappings';

    protected $fillable = [
        'tenant_id',
        'calendar_event_id',
        'external_account_id',
        'provider',
        'external_calendar_id',
        'external_event_id',
        'external_etag',
        'sync_direction',
        'sync_status',
        'sync_error',
        'last_synced_at',
    ];

    protected $casts = [
        'last_synced_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(CalendarEvent::class, 'calendar_event_id');
    }

    public function externalAccount(): BelongsTo
    {
        return $this->belongsTo(CalendarExternalAccount::class, 'external_account_id');
    }
}
