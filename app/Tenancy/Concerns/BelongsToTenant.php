<?php

namespace App\Tenancy\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Administration\Tenant;

/**
 * Scope + auto-asignación de tenant_id al crear.
 * @mixin Model
 */
trait BelongsToTenant
{
    /**
     * Se ejecuta automáticamente cuando el modelo que usa el trait hace boot.
     */
    protected static function bootBelongsToTenant(): void
    {
        // Filtro global por tenant actual
        static::addGlobalScope('tenant', function (Builder $builder): void {
            if ($tenant = Tenant::current()) {
                $builder->where(
                    $builder->getModel()->getTable().'.tenant_id',
                    $tenant->id
                );
            }
        });

        // Auto-set del tenant_id al crear
        static::creating(function (Model $model): void {
            if (!$model->getAttribute('tenant_id')) {
                if ($tenant = Tenant::current()) {
                    $model->setAttribute('tenant_id', $tenant->id);
                }
            }
        });
    }

    /**
     * Scope auxiliar para consultar datos de un tenant específico.
     */
    public function scopeForTenant(Builder $query, Tenant|string $tenant): Builder
    {
        $id = $tenant instanceof Tenant ? $tenant->id : $tenant;

        return $query
            ->withoutGlobalScope('tenant')
            ->where($this->getTable().'.tenant_id', $id);
    }

    /**
     * Relación (si quieres acceder al modelo Tenant).
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}

