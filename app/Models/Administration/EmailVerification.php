<?php

namespace App\Models\Administration;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class EmailVerification extends Model
{
    protected $table = 'email_verifications';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id','user_id','purpose','code_hash','expires_at','sent_at',
        'attempts','max_attempts','consumed_at','locale','ip','user_agent'
    ];

    protected $casts = [
        'expires_at'  => 'datetime',
        'sent_at'     => 'datetime',
        'consumed_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function ($m) {
            if (! $m->id) $m->id = (string) Str::uuid();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return now()->greaterThan($this->expires_at);
    }

    public function isConsumed(): bool
    {
        return ! is_null($this->consumed_at);
    }
}
