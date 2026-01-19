<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Administration\GroupController;

Route::controller(GroupController::class)->group(function () {
    Route::get('groups', 'index')->middleware('permission:List groups');
    Route::post('groups', 'store')->middleware('permission:Create groups');

    Route::post('groups/{group}/invite', 'invite')->middleware('permission:Invite users');

    Route::get('groups/invitations', 'invitations')->middleware('permission:List invitations');

    Route::post('groups/{group}/accept', 'accept')->middleware('permission:Accept users');
    Route::post('groups/{group}/reject', 'reject')->middleware('permission:Reject invitation_users');

    Route::get('groups/{group}/members', 'members')->middleware('permission:List member_users');

    Route::get('groups/{group}/messages', 'messages')->middleware('permission:List messages');
    Route::post('groups/{group}/messages', 'sendMessage')->middleware('permission:Send messages');
});

