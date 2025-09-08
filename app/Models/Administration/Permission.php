<?php

namespace App\Models\Administration;

use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    use HasFactory, Uuid;
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $guard_name = 'api';
}
