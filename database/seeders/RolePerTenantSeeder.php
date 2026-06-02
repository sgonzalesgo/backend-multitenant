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
                "Search evaluation_periods",
                "Store evaluation_periods",
                "Update evaluation_periods",
                "Delete evaluation_periods",
                "List modalities",
                "Search modalities",
                "Store modalities",
                "Update modalities",
                "Delete modalities",
                "List shifts",
                "Search shifts",
                "Store shifts",
                "Update shifts",
                "Delete shifts",
                "List parallels",
                "Search parallels",
                "Store parallels",
                "Update parallels",
                "Delete parallels",
                "List specialties",
                "Search specialties",
                "Store specialties",
                "Update specialties",
                "Delete specialties",
                "List classrooms",
                "Search classrooms",
                "Store classrooms",
                "Update classrooms",
                "Delete classrooms",
                "List educational_levels",
                "Search educational_levels",
                "Store educational_levels",
                "Update educational_levels",
                "Delete educational_levels",
                "List subject_types",
                "Search subject_types",
                "Store subject_types",
                "Update subject_types",
                "Delete subject_types",
                "List evaluation_types",
                "Search evaluation_types",
                "Store evaluation_types",
                "Update evaluation_types",
                "Delete evaluation_types",
                "List subjects",
                "Search subjects",
                "Store subjects",
                "Update subjects",
                "Delete subjects",
                "List instructors",
                "Search instructors",
                "Store instructors",
                "Update instructors",
                "Delete instructors",
                "List students",
                "Store students",
                "Update students",
                "Delete students",
                "List legal_representatives",
                "Search legal_representatives",
                "Store legal_representatives",
                "Update legal_representatives",
                "Delete legal_representatives",
                "List courses",
                "Search courses",
                "Store courses",
                "Update courses",
                "Delete courses",
                "List enrollments",
                "Search enrollments",
                "Store enrollments",
                "Update enrollments",
                "Delete enrollments",
                "List academic_schedules",
                "Search academic_schedules",
                "Store academic_schedules",
                "Update academic_schedules",
                "Delete academic_schedules",
                "List attendances",
                "Search attendances",
                "Store attendances",
                "Update attendances",
                "Delete attendances",
                "Search academic_context",             //filtro general
                "List academic_non_working_days",
                "Search academic_non_working_days",
                "Store academic_non_working_days",
                "Update academic_non_working_days",
                "Delete academic_non_working_days",
                "Reopen attendances",
                "Manage all_attendances",
                "View student_dashboards",
                "List attendance_justifications",
                "Store attendance_justifications",
                "Approve attendance_justifications",
                "Reject attendance_justifications",
                "Delete attendance_justifications",

//                cuantitativos
                "List grade_component_templates",
                "Store grade_component_templates",
                "View grade_component_templates",
                "Update grade_component_templates",
                "Delete grade_component_templates",
                "Generate grade_components",

                "List grade_component_definitions",
                "Store grade_component_definitions",
                "View grade_component_definitions",
                "Update grade_component_definitions",
                "Delete grade_component_definitions",
                "Download grade_excel_templates",

//                 cualitativos
                "List qualitative_evaluation_areas",
                "Search qualitative_evaluation_areas",
                "Store qualitative_evaluation_areas",
                "Update qualitative_evaluation_areas",
                "Delete qualitative_evaluation_areas",

                "List qualitative_skill_definitions",
                "Search qualitative_skill_definitions",
                "Store qualitative_skill_definitions",
                "Update qualitative_skill_definitions",
                "Delete qualitative_skill_definitions",

                "List qualitative_evaluation_templates",
                "Search qualitative_evaluation_templates",
                "Store qualitative_evaluation_templates",
                "Update qualitative_evaluation_templates",
                "Delete qualitative_evaluation_templates",

                "List qualitative_evaluation_components",
                "Store qualitative_evaluation_components",
                "Delete qualitative_evaluation_components",

                "Download qualitative_excel_templates"
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
