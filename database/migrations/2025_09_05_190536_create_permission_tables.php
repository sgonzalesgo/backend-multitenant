<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $teams = config('permission.teams');
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');
        $pivotRole = $columnNames['role_pivot_key'] ?? 'role_id';
        $pivotPermission = $columnNames['permission_pivot_key'] ?? 'permission_id';

        // ----- permissions
        Schema::create($tableNames['permissions'], static function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });

        // ----- roles
        Schema::create($tableNames['roles'], static function (Blueprint $table) use ($teams, $columnNames) {
            $table->uuid('id')->primary();

            if ($teams || config('permission.testing')) {
                // SOLO definir una vez. Si quieres FK a tenants, usa foreignUuid directamente.
                $table->uuid($columnNames['team_foreign_key'])->nullable()
                    ->index('roles_team_foreign_key_index');

                // Opcional: FK a tenants (si aplica)
                // $table->foreignUuid($columnNames['team_foreign_key'])
                //       ->references('id')->on('tenants')->nullOnDelete();
            }

            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();

            if ($teams || config('permission.testing')) {
                $table->unique([$columnNames['team_foreign_key'], 'name', 'guard_name']);
            } else {
                $table->unique(['name', 'guard_name']);
            }
        });

        // ----- model_has_permissions
        Schema::create($tableNames['model_has_permissions'], static function (Blueprint $table) use ($tableNames, $columnNames, $pivotPermission, $teams) {

            // Usar foreignUuid directamente (crea columna + FK):
            $table->foreignUuid($pivotPermission)
                ->references('id')
                ->on($tableNames['permissions'])
                ->cascadeOnDelete();

            $table->string('model_type');
            $table->uuid($columnNames['model_morph_key']);
            $table->index([$columnNames['model_morph_key'], 'model_type'], 'model_has_permissions_model_id_model_type_index');

            if ($teams) {
                $table->uuid($columnNames['team_foreign_key']);
                $table->index($columnNames['team_foreign_key'], 'model_has_permissions_team_foreign_key_index');

                // Opcional: FK del team/tenant
                // $table->foreignUuid($columnNames['team_foreign_key'])
                //       ->references('id')->on('tenants')->cascadeOnDelete();

                $table->primary(
                    [$columnNames['team_foreign_key'], $pivotPermission, $columnNames['model_morph_key'], 'model_type'],
                    'model_has_permissions_permission_model_type_primary'
                );
            } else {
                $table->primary(
                    [$pivotPermission, $columnNames['model_morph_key'], 'model_type'],
                    'model_has_permissions_permission_model_type_primary'
                );
            }
        });

        // ----- model_has_roles
        Schema::create($tableNames['model_has_roles'], static function (Blueprint $table) use ($tableNames, $columnNames, $pivotRole, $teams) {

            $table->foreignUuid($pivotRole)
                ->references('id')
                ->on($tableNames['roles'])
                ->cascadeOnDelete();

            $table->string('model_type');
            // Para evitar columnas duplicadas, definimos explícitamente el ID del morph:
            $table->uuid($columnNames['model_morph_key']);
            $table->index([$columnNames['model_morph_key'], 'model_type'], 'model_has_roles_model_id_model_type_index');

            if ($teams) {
                $table->uuid($columnNames['team_foreign_key']);
                $table->index($columnNames['team_foreign_key'], 'model_has_roles_team_foreign_key_index');

                // Opcional: FK a tenants
                // $table->foreignUuid($columnNames['team_foreign_key'])
                //       ->references('id')->on('tenants')->cascadeOnDelete();

                $table->primary(
                    [$columnNames['team_foreign_key'], $pivotRole, $columnNames['model_morph_key'], 'model_type'],
                    'model_has_roles_role_model_type_primary'
                );
            } else {
                $table->primary(
                    [$pivotRole, $columnNames['model_morph_key'], 'model_type'],
                    'model_has_roles_role_model_type_primary'
                );
            }
        });

        // ----- role_has_permissions
        Schema::create($tableNames['role_has_permissions'], static function (Blueprint $table) use ($tableNames, $pivotRole, $pivotPermission) {

            $table->foreignUuid($pivotPermission)
                ->references('id')
                ->on($tableNames['permissions'])
                ->cascadeOnDelete();

            $table->foreignUuid($pivotRole)
                ->references('id')
                ->on($tableNames['roles'])
                ->cascadeOnDelete();

            $table->primary([$pivotPermission, $pivotRole], 'role_has_permissions_permission_id_role_id_primary');
        });

        app('cache')
            ->store(config('permission.cache.store') != 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));
    }

    public function down(): void
    {
        $tableNames = config('permission.table_names');

        if (empty($tableNames)) {
            throw new \Exception('Error: config/permission.php not found and defaults could not be merged. Please publish the package configuration before proceeding, or drop the tables manually.');
        }

        Schema::drop($tableNames['role_has_permissions']);
        Schema::drop($tableNames['model_has_roles']);
        Schema::drop($tableNames['model_has_permissions']);
        Schema::drop($tableNames['roles']);
        Schema::drop($tableNames['permissions']);
    }
};
