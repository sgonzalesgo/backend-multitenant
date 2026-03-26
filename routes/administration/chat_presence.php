<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Administration\ChatPresenceController;

Route::controller(ChatPresenceController::class)->group(function () {
    Route::post('/chat-presence/active', 'setActive')->middleware('permission:List chat_messages');
    Route::delete('/chat-presence/active', 'clearActive')->middleware('permission:List chat_messages');
});
