<?php

return [
    'permissions' => [
        'list' => 'Permissions list.',
        'all' => 'Full permissions list.',
        'show' => 'Permission detail.',
        'created' => 'Permission created.',
        'updated' => 'Permission updated.',
        'deleted' => 'Permission deleted.',
        'user_in_tenant' => 'User effective permissions in tenant.',
    ],
    'roles_permissions' => [
        'list' => 'Role current permissions.',
        'attach' => 'Permissions attached to role.',
        'revoke' => 'Permissions revoked from role.',
        'sync' => 'Role permissions synchronized.',
    ],
    'roles' => [
        'list'   => 'Roles list.',
        'all'    => 'Full roles list.',
        'show'   => 'Role detail.',
        'created'=> 'Role created.',
        'updated'=> 'Role updated.',
        'deleted'=> 'Role deleted.',
        'user_in_tenant' => 'User roles in tenant.',

        'permissions' => [
            'list' => 'Role permissions listed.',
            'sync' => 'Role permissions synchronized.',
        ],
    ],
    'users' => [
        'list'    => 'Users list.',
        'all'     => 'Full users list.',
        'show'    => 'User detail.',
        'created' => 'User created.',
        'updated' => 'User updated.',
        'deleted' => 'User deleted.',
        'register'=> 'User registered (signup).',
        'social'  => [
            'created' => 'User created via social provider.',
            'updated' => 'User updated/linked via social provider.',
            'upsert'  => 'User upsert via social provider.',
        ],
        'roles' => [
            'sync_in_tenant' => 'User roles synchronized in tenant.',
        ],
    ],
    'auth' => [
        'impersonate' => [
            'start'         => 'Impersonation started.',
            'stop'          => 'Impersonation ended.',
            'stop_no_token' => 'No active impersonation token to revoke.',
            'error'         => 'An error occurred while starting impersonation.',
        ],
    ],
];
