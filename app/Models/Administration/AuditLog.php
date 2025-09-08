<?php

namespace App\Models\Administration;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    use HasUuids;

    protected $table = 'audit_logs';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'actor_type','actor_id','tenant_id',
        'event','auditable_type','auditable_id',
        'description','old_values','new_values','meta',
        'ip_address','user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'meta'       => 'array',
    ];

    // Relaciones útiles
    public function actor(): MorphTo
    {
        return $this->morphTo(null, 'actor_type', 'actor_id');
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo(null, 'auditable_type', 'auditable_id');
    }
}
