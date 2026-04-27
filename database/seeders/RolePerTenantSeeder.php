<?php

namespace Database\Seeders;

use App\Models\Administration\Permission;
use App\Models\Administration\Role;
use App\Models\Administration\Tenant;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class RolePerTenantSeeder extends Seeder
{
    public function run(): void
    {
        $guard = 'api';

        // Roles que quieres crear por cada tenant
        $rolesPerTenant = [
            'admin'  => [
                'List permissions',
                'Search permissions',
                'Create permissions',
                'Update permissions',
                'Delete permissions',
                'List roles',
                'Search roles',
                'Create roles',
                'Update roles',
                'Delete roles',
                'List users',
                'Search users',
                'Create users',
                'Update users',
                'Delete users',
                'List tenants',
                'Search tenants',
                'Create tenants',
                'Update tenants',
                'Delete tenants',
                'Sync role_permissions',
                'Assign role_permissions',
                'Assign roles',
                'Sync user_roles',
                'Register users',
                'Social login',
                'Impersonate users',
                'List audit_logs',
                'Create audit_logs',
                'Create groups',
                'Invite users',
                'List invitations',
                'Accept users',
                'Reject invitation_users',
                'List member_users',
                'Start chat_messages',
                'List chat_messages',
                'Send chat_messages',
                'Read chat_messages',
                'List chat_groups',
                'Create chat_groups',
                'Invite chat_group_users',
                'List chat_group_invitations',
                'Accept chat_group_users',
                'Reject chat_group_invitations',
                'List chat_group_member_users',
                'List chat_group_messages',
                'Send chat_group_messages',
                'Read chat_group_messages',
                'Edit chat_groups',
                'Delete chat_groups',
                'Leave chat_group_users',
                'Remove chat_group_users',
                'Edit chat_group_messages',
                'Delete chat_group_messages',
                'Send manual_notifications',
                'List manual_notifications',
                'Archive manual_notifications',
                'Unarchive manual_notifications',
                'List events',
                'Store events',
                'Show events',
                'Update events',
                'Update events',
                'Delete events',
                'Store events',
                'Show event_type',
                'List event_types',
                'Store event_types',
                'Update event_types',
                'Delete event_types',
                'List persons',
                'Store persons',
                'Show persons',
                'Update persons',
                'Delete persons',
                'List instructors',
                'Store instructors',
                'Show instructors',
                'Update instructors',
                'Delete instructors',
                'List notifications',
                'Read notifications',
                'List positions',
                'Search positions',
                'Search positions',
                'Store positions',
                'Update positions',
                'List tenant_positions',
                'Search tenant_positions',
                'Store tenant_positions',
                'Update tenant_positions',
                "List departments",
                "Store departments",
                "Update departments",
                "Delete departments",
                "List enrollment_statuses",
                "Store enrollment_statuses",
                "Update enrollment_statuses",
                "Delete enrollment_statuses",
                "List academic_years",
                "Store academic_years",
                "Update academic_years",
                "Delete academic_years",
                "List evaluation_periods",
                "Store evaluation_periods",
                "Update evaluation_periods",
                "Delete evaluation_periods",
                "List modalities",
                "Store modalities",
                "Update modalities",
                "Delete modalities",
                "List shifts",
                "Store shifts",
                "Update shifts",
                "Delete shifts",
                "List parallels",
                "Store parallels",
                "Update parallels",
                "Delete parallels",
                "List specialties",
                "Store specialties",
                "Update specialties",
                "Delete specialties",
                "List classrooms",
                "Store classrooms",
                "Update classrooms",
                "Delete classrooms",
                "List educational_levels",
                "Store educational_levels",
                "Update educational_levels",
                "Delete educational_levels",
                "List subject_types",
                "Store subject_types",
                "Update subject_types",
                "Delete subject_types",
                "List evaluation_types",
                "Store evaluation_types",
                "Update evaluation_types",
                "Delete evaluation_types",
                "List subjects",
                "Store subjects",
                "Update subjects",
                "Delete subjects",
            ],
        ];

        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            // Marca tenant actual (Spatie Multitenancy)
            $tenant->makeCurrent();

            // Fija el team_id para spatie/permission (¡crítico!)
            app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);

            foreach ($rolesPerTenant as $roleName => $perms) {
                // Rol por tenant
                $role = Role::firstOrCreate(
                    ['name' => $roleName, 'guard_name' => $guard, 'tenant_id' => $tenant->id],
                    []
                );

                // Permisos globales a asignar al rol de este tenant
                $permissions = Permission::whereIn('name', $perms)
                    ->where('guard_name', $guard)
                    ->get();

                $role->syncPermissions($permissions);
            }
        }

        // Limpia "current" para evitar efectos colaterales
        Tenant::forgetCurrent();
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
    }
}
