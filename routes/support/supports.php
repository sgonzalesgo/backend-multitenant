<?php


use App\Http\Controllers\Support\SupportTicketController;
use Illuminate\Support\Facades\Route;

Route::controller(SupportTicketController::class)->group(function () {
    Route::get('support-tickets', 'index')->middleware('permission:List support_tickets');
    Route::post('support-tickets', 'store')->middleware('permission:Store support_tickets');
    Route::get('support-tickets/{supportTicket}', 'show')->middleware('permission:List support_tickets');
    Route::post('support-tickets/{supportTicket}', 'update')->middleware('permission:Update support_tickets');
    Route::post('support-tickets/{supportTicket}/assign', 'assign')->middleware('permission:Assign support_tickets');
    Route::post('support-tickets/{supportTicket}/status', 'changeStatus')->middleware('permission:Change status_support_tickets');
    Route::post('support-tickets/{supportTicket}/comments', 'comment')->middleware('permission:Update support_tickets');
    Route::delete('support-tickets/{supportTicket}', 'destroy')->middleware('permission:Delete support_tickets');
    Route::post('support-tickets/{supportTicket}/comments/{comment}', 'updateComment')->middleware('permission:Update support_tickets');
    Route::delete('support-tickets/{supportTicket}/comments/{comment}', 'deleteComment')->middleware('permission:Update support_tickets');
    Route::delete('support-tickets/{supportTicket}/attachments/{attachment}', 'deleteAttachment')->middleware('permission:Update support_tickets');
    Route::post('support-tickets/{supportTicket}/attachments', 'addAttachments')->middleware('permission:Update support_tickets');
});
