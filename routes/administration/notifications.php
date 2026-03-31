<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Administration\NotificationController;

//Route::controller(NotificationController::class)->group(function () {
//    Route::get('/notifications', 'index')->middleware('permission:List notifications');
//    Route::get('/notifications/unread-count', 'countUnread')->middleware('permission:List notifications');
//
//    Route::patch('/notifications/chat/read', 'markChatAsRead')->middleware('permission:Read notifications');
//    Route::patch('/notifications/read-all', 'markAllAsRead')->middleware('permission:Read notifications');
//    Route::patch('/notifications/{notificationId}/read', 'markAsRead')->middleware('permission:Read notifications');
//});

Route::controller(NotificationController::class)->group(function () {
    Route::get('/notifications', 'index');
    Route::get('/notifications/unread-count', 'countUnread');
    Route::patch('/notifications/chat/read', 'markChatAsRead');
    Route::patch('/notifications/read-all', 'markAllAsRead');
    Route::patch('/notifications/{notificationId}/read', 'markAsRead');
});
