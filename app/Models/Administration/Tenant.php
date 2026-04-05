<?php

namespace App\Models\Administration;

// global import
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Multitenancy\Models\Tenant as BaseTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;

// local import
use App\Traits\Uuid;


class Tenant extends BaseTenant
{
    use HasFactory, Uuid, Notifiable;

    protected $table = 'tenants';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'domain',
        'logo',
        'address',
        'phone',
        'email',
        'legal_id',
        'legal_id_type',
        'is_active',
        'business_name',
        'campus_logo',
        'campus_type',
        'slogan',
        'amie_code',
        'city',
        'state',
        'country',
        'country_logo',
        'country_logo_position_right',
        'zip',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'country_logo_position_right' => 'boolean',
    ];

    public function tenantPositions(): HasMany
    {
        return $this->hasMany(TenantPosition::class);
    }
}
