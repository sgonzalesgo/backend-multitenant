<?php

namespace App\Models\Calendar;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Administration\Tenant;
use App\Models\Administration\User;

class CalendarExternalAccount extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'calendar_external_accounts';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'provider',
        'provider_account_email',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'scopes',
        'sync_enabled',
    ];

    protected $casts = [
        'token_expires_at' => 'datetime',
        'scopes' => 'array',
        'sync_enabled' => 'boolean',
    ];

    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function mappings(): HasMany
    {
        return $this->hasMany(CalendarExternalMapping::class, 'external_account_id');
    }
}
