<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Http\Requests\Administration\Permission\StorePermissionRequest;
use App\Http\Requests\Administration\Permission\SyncRolePermissionsRequest;
use App\Http\Requests\Administration\Permission\UpdatePermissionRequest;
use App\Models\Administration\Permission;
use App\Models\Administration\Role;
use App\Repositories\Administration\PermissionRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PermissionController extends Controller
{
    public function __construct(private PermissionRepository $repo) {}

    public function index(Request $req): JsonResponse
    {
        $data = $this->repo->list($req->only(['q','sort','dir','per_page']));
        return response()->json(['code'=>200,'message'=>__('messages.permissions.listed'),'data'=>$data,'error'=>null], Response::HTTP_OK);
    }

    public function store(StorePermissionRequest $req): JsonResponse
    {
        $perm = $this->repo->create($req->validated());
        return response()->json(['code'=>201,'message'=>__('messages.permissions.created'),'data'=>$perm,'error'=>null], Response::HTTP_CREATED);
    }

    public function show(Permission $permission): JsonResponse
    {
        return response()->json(['code'=>200,'message'=>__('messages.permissions.shown'),'data'=>$permission,'error'=>null], Response::HTTP_OK);
    }

    public function update(UpdatePermissionRequest $req, Permission $permission): JsonResponse
    {
        $perm = $this->repo->update($permission, $req->validated());
        return response()->json(['code'=>200,'message'=>__('messages.permissions.updated'),'data'=>$perm,'error'=>null], Response::HTTP_OK);
    }

    public function destroy(Permission $permission): JsonResponse
    {
        $this->repo->delete($permission);
        return response()->json(['code'=>200,'message'=>__('messages.permissions.deleted'),'data'=>null,'error'=>null], Response::HTTP_OK);
    }

    // Role ↔ Permissions (SYNC only, por IDs)
    public function listRolePermissions(Role $role): JsonResponse
    {
        $perms = $this->repo->permissionsForRole($role);
        return response()->json(['code'=>200,'message'=>__('messages.roles.permissions_listed'),'data'=>$perms,'error'=>null], Response::HTTP_OK);
    }

    public function syncRolePermissions(SyncRolePermissionsRequest $req, Role $role): JsonResponse
    {
        $role = $this->repo->syncRolePermissionsByIds($role, $req->input('permissions', []));
        return response()->json(['code'=>200,'message'=>__('messages.roles.permissions_synced'),'data'=>$role,'error'=>null], Response::HTTP_OK);
    }
}
