<?php

return [
    'permissions' => [
        'list' => 'Listado de permisos.',
        'all' => 'Listado completo de permisos.',
        'show' => 'Detalle de permiso.',
        'created' => 'Permiso creado.',
        'updated' => 'Permiso actualizado.',
        'deleted' => 'Permiso eliminado.',
        'user_in_tenant' => 'Permisos efectivos del usuario en el tenant.',
    ],
    'roles_permissions' => [
        'list' => 'Permisos actuales del rol.',
        'attach' => 'Permisos adjuntados al rol.',
        'revoke' => 'Permisos revocados del rol.',
        'sync' => 'Permisos del rol sincronizados.',
    ],

    'roles' => [
        'list'   => 'Listado de roles.',
        'all'    => 'Listado completo de roles.',
        'show'   => 'Detalle de rol.',
        'created'=> 'Rol creado.',
        'updated'=> 'Rol actualizado.',
        'deleted'=> 'Rol eliminado.',
        'user_in_tenant' => 'Roles del usuario en el tenant.',

        'permissions' => [
            'list' => 'Permisos del rol listados.',
            'sync' => 'Permisos del rol sincronizados.',
        ],
    ],
    'users' => [
        'list'    => 'Listado de usuarios.',
        'all'     => 'Listado completo de usuarios.',
        'show'    => 'Detalle de usuario.',
        'created' => 'Usuario creado.',
        'updated' => 'Usuario actualizado.',
        'deleted' => 'Usuario eliminado.',
        'register'=> 'Usuario registrado (signup).',
        'social'  => [
            'created' => 'Usuario creado vía proveedor social.',
            'updated' => 'Usuario actualizado/vinculado vía proveedor social.',
            'upsert'  => 'Upsert de usuario vía proveedor social.',
        ],
        'roles' => [
            'sync_in_tenant' => 'Roles de usuario sincronizados en el tenant.',
        ],
    ],
    'auth' => [
        'impersonate' => [
            'start'         => 'Suplantación iniciada.',
            'stop'          => 'Suplantación finalizada.',
            'stop_no_token' => 'No hay token de suplantación activo para revocar.',
            'error'         => 'Ocurrió un error al iniciar la suplantación.',
        ],
    ],
];
