<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Administration\GroupController;

Route::controller(GroupController::class)->group(function () {
    Route::get('groups', 'index')->middleware('permission:List chat_groups');
    Route::post('groups', 'store')->middleware('permission:Create chat_groups');
    Route::post('groups/{group}/invite', 'invite')->middleware('permission:Invite chat_group_users');
    Route::get('groups/invitations', 'invitations')->middleware('permission:List chat_group_invitations');
    Route::post('groups/{group}/accept', 'accept')->middleware('permission:Accept chat_group_users');
    Route::post('groups/{group}/reject', 'reject')->middleware('permission:Reject chat_group_invitations');
    Route::get('groups/{group}/members', 'members')->middleware('permission:List chat_group_member_users');
    Route::get('groups/{group}/messages', 'messages')->middleware('permission:List chat_group_messages');
    Route::post('groups/{group}/messages', 'sendMessage')->middleware('permission:Send chat_group_messages');
    Route::post('groups/{group}/read', 'read')->middleware('permission:Read chat_group_messages');
    Route::patch('groups/{group}', 'update')->middleware('permission:Edit chat_groups');
    Route::delete('groups/{group}', 'destroy')->middleware('permission:Delete chat_groups');
    Route::post('groups/{group}/leave', 'leave')->middleware('permission:Leave chat_group_users');
    Route::delete('groups/{group}/members/{userId}', 'removeMember')->middleware('permission:Remove chat_group_users');
    Route::patch('groups/{group}/messages/{messageId}', 'updateMessage')->middleware('permission:Edit chat_group_messages');
    Route::delete('groups/{group}/messages/{messageId}', 'deleteMessage')->middleware('permission:Delete chat_group_messages');

    Route::patch('groups/{group}/hide', 'hide')->middleware('permission:List chat_groups');
    Route::patch('groups/{group}/unhide', 'unhide')->middleware('permission:List chat_groups');
    Route::get('groups/hidden', 'hidden')->middleware('permission:List chat_groups');

});
