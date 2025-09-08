<?php

namespace App\Observers;

use App\Repositories\Administration\AuditLogRepository;
use Illuminate\Database\Eloquent\Model;
use App\Models\Administration\Tenant;

class AuditableObserver
{
    public function created(Model $model): void
    {
        app(AuditLogRepository::class)->log(
            actor: auth()->user(),
            event: 'created',
            subject: $model,
            description: 'Registro creado',
            changes: [
                'old' => null,
                'new' => $this->visibleAttributes($model->getAttributes(), $model),
            ],
            tenantId: Tenant::current()?->id
        );
    }

    public function updated(Model $model): void
    {
        // Solo los cambios
        $old = $this->visibleAttributes($model->getOriginal(), $model);
        $new = $this->visibleAttributes($model->getChanges(), $model);

        app(AuditLogRepository::class)->log(
            actor: auth()->user(),
            event: 'updated',
            subject: $model,
            description: 'Registro actualizado',
            changes: compact('old', 'new'),
            tenantId: Tenant::current()?->id
        );
    }

    public function deleted(Model $model): void
    {
        app(AuditLogRepository::class)->log(
            actor: auth()->user(),
            event: 'deleted',
            subject: $model,
            description: 'Registro eliminado',
            changes: [
                'old' => $this->visibleAttributes($model->getOriginal(), $model),
                'new' => null,
            ],
            tenantId: Tenant::current()?->id
        );
    }

    protected function visibleAttributes(array $attributes, Model $model): array
    {
        // Excluir hidden y casts complejos si hace falta
        foreach ($model->getHidden() as $hidden) {
            unset($attributes[$hidden]);
        }
        // Evita incluir password/tokens/etc si no estuvieran ya en hidden
        unset($attributes['password'], $attributes['remember_token']);
        return $attributes;
    }
}
