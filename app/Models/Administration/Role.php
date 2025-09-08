<?php

namespace App\Models\Administration;

use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    use HasFactory, Uuid;
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guard_name = 'api';
}
