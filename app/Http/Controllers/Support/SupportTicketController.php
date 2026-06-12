<?php

namespace App\Http\Controllers\Support;

use App\Http\Controllers\Controller;
use App\Http\Requests\Support\AssignSupportTicketRequest;
use App\Http\Requests\Support\ChangeSupportTicketStatusRequest;
use App\Http\Requests\Support\StoreSupportTicketCommentRequest;
use App\Http\Requests\Support\StoreSupportTicketRequest;
use App\Http\Requests\Support\UpdateSupportTicketRequest;
use App\Models\Support\SupportTicket;
use App\Models\Support\SupportTicketAttachment;
use App\Models\Support\SupportTicketComment;
use App\Repositories\Support\SupportTicketRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SupportTicketController extends Controller
{
    public function __construct(private SupportTicketRepository $repo)
    {
    }

    public function index(Request $req): JsonResponse
    {
        $data = $this->repo->list($req->only(['q', 'sort', 'dir', 'per_page']));

        return response()->json([
            'code' => 200,
            'message' => __('messages.support_tickets.listed'),
            'data' => $data,
            'error' => null,
        ], Response::HTTP_OK);
    }

    public function store(StoreSupportTicketRequest $req): JsonResponse
    {
        $ticket = $this->repo->create($req);

        return response()->json([
            'code' => 201,
            'message' => __('messages.support_tickets.created'),
            'data' => $ticket,
            'error' => null,
        ], Response::HTTP_CREATED);
    }

    public function show(SupportTicket $supportTicket): JsonResponse
    {
        $ticket = $this->repo->findOrFail($supportTicket->id);

        return response()->json([
            'code' => 200,
            'message' => __('messages.support_tickets.shown'),
            'data' => $ticket,
            'error' => null,
        ], Response::HTTP_OK);
    }

    public function update(UpdateSupportTicketRequest $req, SupportTicket $supportTicket): JsonResponse
    {
        $ticket = $this->repo->update($supportTicket, $req);

        return response()->json([
            'code' => 200,
            'message' => __('messages.support_tickets.updated'),
            'data' => $ticket,
            'error' => null,
        ], Response::HTTP_OK);
    }

    public function assign(AssignSupportTicketRequest $req, SupportTicket $supportTicket): JsonResponse
    {
        $ticket = $this->repo->assign($supportTicket, $req);

        return response()->json([
            'code' => 200,
            'message' => __('messages.support_tickets.assigned'),
            'data' => $ticket,
            'error' => null,
        ], Response::HTTP_OK);
    }

    public function changeStatus(ChangeSupportTicketStatusRequest $req, SupportTicket $supportTicket): JsonResponse
    {
        $ticket = $this->repo->changeStatus($supportTicket, $req);

        return response()->json([
            'code' => 200,
            'message' => __('messages.support_tickets.status_changed'),
            'data' => $ticket,
            'error' => null,
        ], Response::HTTP_OK);
    }

    public function comment(StoreSupportTicketCommentRequest $req, SupportTicket $supportTicket): JsonResponse
    {
        $ticket = $this->repo->comment($supportTicket, $req);

        return response()->json([
            'code' => 201,
            'message' => __('messages.support_tickets.comment_created'),
            'data' => $ticket,
            'error' => null,
        ], Response::HTTP_CREATED);
    }

    public function destroy(SupportTicket $supportTicket): JsonResponse
    {
        $this->repo->delete($supportTicket);

        return response()->json([
            'code' => 200,
            'message' => __('messages.support_tickets.deleted'),
            'data' => null,
            'error' => null,
        ], Response::HTTP_OK);
    }

    public function updateComment(
        StoreSupportTicketCommentRequest $req,
        SupportTicket $supportTicket,
        SupportTicketComment $comment
    ): JsonResponse {
        $ticket = $this->repo->updateComment($supportTicket, $comment, $req);

        return response()->json([
            'code' => 200,
            'message' => __('messages.support_tickets.comment_updated'),
            'data' => $ticket,
            'error' => null,
        ], Response::HTTP_OK);
    }

    public function deleteComment(
        SupportTicket $supportTicket,
        SupportTicketComment $comment
    ): JsonResponse {
        $ticket = $this->repo->deleteComment($supportTicket, $comment);

        return response()->json([
            'code' => 200,
            'message' => __('messages.support_tickets.comment_deleted'),
            'data' => $ticket,
            'error' => null,
        ], Response::HTTP_OK);
    }

    public function addAttachments(Request $req, SupportTicket $supportTicket): JsonResponse
    {
        $req->validate([
            'attachments' => ['required', 'array'],
            'attachments.*' => ['file', 'max:5120'],
        ]);

        $ticket = $this->repo->addAttachments($supportTicket, $req);

        return response()->json([
            'code' => 200,
            'message' => __('messages.support_tickets.attachments_added'),
            'data' => $ticket,
            'error' => null,
        ], Response::HTTP_OK);
    }

    public function deleteAttachment(
        SupportTicket $supportTicket,
        SupportTicketAttachment $attachment
    ): JsonResponse {
        $ticket = $this->repo->deleteAttachment($supportTicket, $attachment);

        return response()->json([
            'code' => 200,
            'message' => __('messages.support_tickets.attachment_deleted'),
            'data' => $ticket,
            'error' => null,
        ], Response::HTTP_OK);
    }
}
