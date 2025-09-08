<?php

namespace App\Models\Administration;

use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use Spatie\Multitenancy\Models\Tenant as BaseTenant;

class Tenant extends BaseTenant
{
    use HasFactory, Uuid, Notifiable;

    protected $table = 'tenants';
    protected $primaryKey = 'id';
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'domain',   // o subdomain, o slug, lo que uses para resolver el tenant
        'data',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    // Relaciones útiles
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
