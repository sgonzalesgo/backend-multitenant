<?php

namespace App\Models\Calendar;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Administration\Tenant;
use App\Models\Administration\User;

class CalendarEvent extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'calendar_events';

    protected $fillable = [
        'tenant_id',
        'event_type_id',
        'created_by',
        'updated_by',
        'title',
        'description',
        'location',
        'url',
        'start_at',
        'end_at',
        'all_day',
        'timezone',
        'status',
        'visibility',
        'source',
        'editable_by',
        'color',
        'is_recurring',
        'recurrence_rule',
        'google_sync_enabled',
        'google_last_synced_at',
        'metadata',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'all_day' => 'boolean',
        'is_recurring' => 'boolean',
        'google_sync_enabled' => 'boolean',
        'google_last_synced_at' => 'datetime',
        'metadata' => 'array',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function eventType(): BelongsTo
    {
        return $this->belongsTo(CalendarEventType::class, 'event_type_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(CalendarEventParticipant::class, 'calendar_event_id');
    }

    public function audiences(): HasMany
    {
        return $this->hasMany(CalendarEventAudience::class, 'calendar_event_id');
    }

    public function externalMappings(): HasMany
    {
        return $this->hasMany(CalendarExternalMapping::class, 'calendar_event_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeForTenant(Builder $query, string $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeBetweenDates(Builder $query, string $start, string $end): Builder
    {
        return $query->where(function (Builder $q) use ($start, $end) {
            $q->whereBetween('start_at', [$start, $end])
                ->orWhereBetween('end_at', [$start, $end])
                ->orWhere(function (Builder $sub) use ($start, $end) {
                    $sub->where('start_at', '<=', $start)
                        ->where('end_at', '>=', $end);
                });
        });
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->whereIn('status', ['draft', 'confirmed', 'cancelled']);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', '!=', 'cancelled');
    }

    public function scopeCreatedBy(Builder $query, string $userId): Builder
    {
        return $query->where('created_by', $userId);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isEditableBy(User $user): bool
    {
        if ($this->editable_by === 'system') {
            return false;
        }

        if ($this->editable_by === 'creator_only') {
            return (string) $this->created_by === (string) $user->id;
        }

        if ($this->editable_by === 'admins') {
            return (string) $this->created_by === (string) $user->id;
        }

        return false;
    }
}
