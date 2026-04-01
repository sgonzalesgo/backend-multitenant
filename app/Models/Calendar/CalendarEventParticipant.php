<?php

namespace App\Models\Calendar;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Administration\Tenant;
use App\Models\Administration\User;

class CalendarEventParticipant extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'calendar_event_participants';

    protected $fillable = [
        'tenant_id',
        'calendar_event_id',
        'user_id',
        'person_id',
        'participant_type',
        'role',
        'response_status',
        'is_required',
        'can_view',
        'can_receive_notifications',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'can_view' => 'boolean',
        'can_receive_notifications' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(CalendarEvent::class, 'calendar_event_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
