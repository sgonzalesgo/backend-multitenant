<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Administration\GroupController;

Route::controller(GroupController::class)->group(function () {
    Route::get('groups', 'index')->middleware('permission:List chat_groups');
    Route::post('groups', 'store')->middleware('permission:Create chat_groups');
    Route::post('groups/{group}/invite', 'invite')->middleware('permission:Invite chat_group_users');
    Route::get('groups/invitations', 'invitations')->middleware('permission:List chat_group_invitations');
    Route::post('groups/{group}/accept', 'accept')->middleware('permission:Accept chat_group_users');
    Route::post('groups/{group}/reject', 'reject')->middleware('permission:Reject chat_group_invitation_users');
    Route::get('groups/{group}/members', 'members')->middleware('permission:List chat_group_member_users');
    Route::get('groups/{group}/messages', 'messages')->middleware('permission:List chat_group_messages');
    Route::post('groups/{group}/messages', 'sendMessage')->middleware('permission:Send chat_group_messages');
    Route::post('groups/{groupId}/read', 'read')->middleware('permission:Read chat_group_messages');
});
