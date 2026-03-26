<?php

namespace App\Models\Administration;

// global import
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Permission\Models\Permission as SpatiePermission;

// local import
use App\Traits\Uuid;

class Permission extends SpatiePermission
{
    use HasFactory, Uuid;

    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guard_name = 'api';

    /**
     * Usuarios con permiso directo.
     */
    public function users(): BelongsToMany
    {
        return $this->morphedByMany(
            User::class,
            'model',
            config('permission.table_names.model_has_permissions'),
            'permission_id',
            'model_id'
        );
    }

    /**
     * Asignaciones directas del permiso a cualquier modelo.
     */
    public function modelPermissionAssignments(): HasMany
    {
        return $this->hasMany(
            ModelHasPermission::class,
            'permission_id',
            'id'
        );
    }
}
