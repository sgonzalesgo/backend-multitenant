<?php

namespace App\Models\Administration;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ImpersonationSession extends Model
{
    use HasUuids;

    protected $table = 'impersonation_sessions';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'session_id',
        'impersonator_id',
        'impersonated_id',
        'actor_tenant_id',
        'backup_access_token',
        'backup_refresh_token',
        'started_at',
        'expires_at',
        'ended_at',
        'revoked_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'expires_at' => 'datetime',
        'ended_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    protected $hidden = [
        'backup_access_token',
        'backup_refresh_token',
    ];

    public function impersonator()
    {
        return $this->belongsTo(User::class, 'impersonator_id');
    }

    public function impersonated()
    {
        return $this->belongsTo(User::class, 'impersonated_id');
    }

    public function actorTenant()
    {
        return $this->belongsTo(Tenant::class, 'actor_tenant_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->whereNull('ended_at')
            ->whereNull('revoked_at')
            ->where(function (Builder $q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    public function isActive(): bool
    {
        if ($this->ended_at !== null) {
            return false;
        }

        if ($this->revoked_at !== null) {
            return false;
        }

        if ($this->expires_at !== null && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    public function markEnded(): void
    {
        $this->forceFill([
            'ended_at' => now(),
        ])->save();
    }

    public function markRevoked(): void
    {
        $this->forceFill([
            'revoked_at' => now(),
        ])->save();
    }
}
