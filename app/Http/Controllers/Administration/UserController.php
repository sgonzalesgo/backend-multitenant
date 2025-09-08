<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Http\Requests\Administration\User\StoreUserRequest;
use App\Http\Requests\Administration\User\UpdateUserRequest;
use App\Models\Administration\User;
use App\Repositories\Administration\UserRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{
    public function __construct(private UserRepository $repo) {}

    public function index(Request $req): JsonResponse
    {
        $data = $this->repo->list($req->only(['q','sort','dir','per_page']));
        return response()->json(['code'=>200,'message'=>__('messages.users.listed'),'data'=>$data,'error'=>null], Response::HTTP_OK);
    }

    public function store(StoreUserRequest $req): JsonResponse
    {
        $user = $this->repo->create($req->validated());
        return response()->json(['code'=>201,'message'=>__('messages.users.created'),'data'=>$user,'error'=>null], Response::HTTP_CREATED);
    }

    public function show(User $user): JsonResponse
    {
        return response()->json(['code'=>200,'message'=>__('messages.users.shown'),'data'=>$user,'error'=>null], Response::HTTP_OK);
    }

    public function update(UpdateUserRequest $req, User $user): JsonResponse
    {
        $user = $this->repo->update($user, $req->validated());
        return response()->json(['code'=>200,'message'=>__('messages.users.updated'),'data'=>$user,'error'=>null], Response::HTTP_OK);
    }

    public function destroy(User $user): JsonResponse
    {
        $this->repo->delete($user);
        return response()->json(['code'=>200,'message'=>__('messages.users.deleted'),'data'=>null,'error'=>null], Response::HTTP_OK);
    }
}
