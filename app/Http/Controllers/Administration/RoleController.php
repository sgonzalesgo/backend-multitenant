<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Http\Requests\Administration\Permission\SyncRolePermissionsRequest;
use App\Http\Requests\Administration\Role\StoreRoleRequest;
use App\Http\Requests\Administration\Role\SyncUserRolesInTenantRequest;
use App\Http\Requests\Administration\Role\UpdateRoleRequest;
use App\Models\Administration\Role;
use App\Repositories\Administration\RoleRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleController extends Controller
{
    public function __construct(private RoleRepository $repo) {}

    // CRUD
    public function index(Request $req): JsonResponse
    {
        $data = $this->repo->list($req->only(['q','sort','dir','per_page','tenant_id']));
        return response()->json(['code'=>200,'message'=>__('messages.roles.listed'),'data'=>$data,'error'=>null], Response::HTTP_OK);
    }

    public function store(StoreRoleRequest $req): JsonResponse
    {
        $role = $this->repo->create($req->validated());
        return response()->json(['code'=>201,'message'=>__('messages.roles.created'),'data'=>$role,'error'=>null], Response::HTTP_CREATED);
    }

    public function show(Role $role): JsonResponse
    {
        return response()->json(['code'=>200,'message'=>__('messages.roles.shown'),'data'=>$role,'error'=>null], Response::HTTP_OK);
    }

    public function update(UpdateRoleRequest $req, Role $role): JsonResponse
    {
        $role = $this->repo->update($role, $req->validated());
        return response()->json(['code'=>200,'message'=>__('messages.roles.updated'),'data'=>$role,'error'=>null], Response::HTTP_OK);
    }

    public function destroy(Role $role): JsonResponse
    {
        $this->repo->delete($role);
        return response()->json(['code'=>200,'message'=>__('messages.roles.deleted'),'data'=>null,'error'=>null], Response::HTTP_OK);
    }

    // Permisos del rol (globales) — sync total
    public function listPermissions(Role $role): JsonResponse
    {
        $perms = $this->repo->permissions($role);
        return response()->json(['code'=>200,'message'=>__('messages.roles.permissions_listed'),'data'=>$perms,'error'=>null], Response::HTTP_OK);
    }

    public function syncPermissions(SyncRolePermissionsRequest $req, Role $role): JsonResponse
    {
        $role = $this->repo->syncPermissionsByIds($role, $req->input('permissions', []));
        return response()->json(['code'=>200,'message'=>__('messages.roles.permissions_synced'),'data'=>$role,'error'=>null], Response::HTTP_OK);
    }

    // Sync total de roles del usuario en tenant
    public function syncUserRoles(SyncUserRolesInTenantRequest $req): JsonResponse
    {
        $ids = $this->repo->syncUserRolesInTenant(
            $req->input('user_id'),
            $req->input('tenant_id'),
            $req->input('roles', [])
        );

        return response()->json([
            'code'=>200,'message'=>__('messages.roles.assigned_to_user'),
            'data'=>['role_ids'=>$ids],'error'=>null
        ], Response::HTTP_OK);
    }
}
