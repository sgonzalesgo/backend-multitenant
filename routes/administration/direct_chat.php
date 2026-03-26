<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Administration\DirectMessageController;

Route::controller(DirectMessageController::class)->group(function () {
    // Ver mensajes
    Route::get('/dm', 'index')->middleware('permission:List chat_messages');

    // Obtener los mensajes de un usuario
    Route::post('/dm/start', 'start')->middleware('permission:Start chat_messages');

    // Ver listado de mensajes
    Route::get('/dm/{conversationId}/messages', 'messages')->middleware('permission:List chat_messages');

    // Comenzar a enviar mensajes directos a un usuario
    Route::post('/dm/{conversationId}/messages', 'send')->middleware('permission:Send chat_messages');

    // Ver mensajes leídos
    Route::post('/dm/{conversationId}/read', 'read')->middleware('permission:Read chat_messages');

    Route::patch('/dm/{conversationId}/messages/{messageId}', 'updateMessage')->middleware('permission:Edit chat_messages');
    Route::delete('/dm/{conversationId}/messages/{messageId}', 'deleteMessage')->middleware('permission:Delete chat_messages');

    Route::patch('/dm/{conversationId}/hide', 'hide')->middleware('permission:Start chat_messages');
    Route::patch('/dm/{conversationId}/unhide', 'unhide')->middleware('permission:Start chat_messages');
    Route::get('/dm/hidden', 'hidden')->middleware('permission:List chat_messages');
});
