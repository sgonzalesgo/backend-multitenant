<?php

namespace App\Models\Administration;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Spatie\Multitenancy\Models\Tenant as BaseTenant;

class Tenant extends BaseTenant
{
    use HasUlids;

    protected $table = 'tenants';

    protected $fillable = [
        'name',
        'domain',   // o subdomain, o slug, lo que uses para resolver el tenant
        'data',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    // Relaciones útiles
    public function users(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(User::class);
    }
}
