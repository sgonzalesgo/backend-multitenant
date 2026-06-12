<?php


namespace App\Repositories\Support;

use App\Enums\Support\SupportTicketCategory;
use App\Enums\Support\SupportTicketPriority;
use App\Enums\Support\SupportTicketStatus;
use App\Http\Requests\Support\AssignSupportTicketRequest;
use App\Http\Requests\Support\ChangeSupportTicketStatusRequest;
use App\Http\Requests\Support\StoreSupportTicketCommentRequest;
use App\Http\Requests\Support\StoreSupportTicketRequest;
use App\Http\Requests\Support\UpdateSupportTicketRequest;
use App\Models\Administration\Tenant;
use App\Models\Support\SupportTicket;
use App\Models\Support\SupportTicketAttachment;
use App\Models\Support\SupportTicketComment;
use App\Models\Administration\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class SupportTicketRepository
{
    protected function baseQuery(): Builder
    {
        return SupportTicket::query()
            ->with([
                'tenant:id,name,domain',
                'createdBy:id,name,email',
                'assignedTo:id,name,email',
                'comments.user:id,name,email',
                'comments.attachments',
                'attachments',
            ]);
    }

    protected function resolveCurrentTenantId(): ?string
    {
        if ($current = Tenant::current()) {
            return (string)$current->id;
        }

        $user = auth()->user();

        if (!$user || !method_exists($user, 'token')) {
            return null;
        }

        $token = $user->token();

        if (!$token || empty($token->tenant_id)) {
            return null;
        }

        return (string)$token->tenant_id;
    }

    protected function resolveDefaultAssigneeId(): ?string
    {
        $admin = User::query()
            ->whereHas('roles', function ($query) {
                $query->where('name', 'Admin');
            })
            ->first();

        return $admin?->id;
    }

    public function list(array $filters = []): LengthAwarePaginator
    {
        $rawQ = trim((string)Arr::get($filters, 'q', ''));
        $sort = Arr::get($filters, 'sort', 'created_at');
        $dir = strtolower((string)Arr::get($filters, 'dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $perPage = max(1, min((int)Arr::get($filters, 'per_page', 15), 100));

        if (!in_array($sort, ['title', 'category', 'priority', 'status', 'created_at', 'updated_at'], true)) {
            $sort = 'created_at';
        }

        $global = '';
        $title = '';
        $status = '';
        $priority = '';
        $category = '';
        $tenant = '';
        $createdBy = '';
        $assignedTo = '';
        $createdAtInput = '';

        if ($rawQ !== '') {
            $decoded = json_decode($rawQ, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $global = trim((string)Arr::get($decoded, 'global', ''));
                $title = trim((string)Arr::get($decoded, 'columns.title', ''));
                $status = trim((string)Arr::get($decoded, 'columns.status', ''));
                $priority = trim((string)Arr::get($decoded, 'columns.priority', ''));
                $category = trim((string)Arr::get($decoded, 'columns.category', ''));
                $tenant = trim((string)Arr::get($decoded, 'columns.tenant', ''));
                $createdBy = trim((string)Arr::get($decoded, 'columns.created_by', ''));
                $assignedTo = trim((string)Arr::get($decoded, 'columns.assigned_to', ''));
                $createdAtInput = trim((string)Arr::get($decoded, 'columns.created_at', ''));
            } else {
                $global = $rawQ;
            }
        }

        $ticketsTable = (new SupportTicket())->getTable();

        $query = $this->baseQuery();

        $query
            ->when($global !== '', function ($query) use ($global, $ticketsTable) {
                $query->where(function ($sub) use ($global, $ticketsTable) {
                    $sub->where("{$ticketsTable}.title", 'ilike', "%{$global}%")
                        ->orWhere("{$ticketsTable}.description", 'ilike', "%{$global}%")
                        ->orWhere("{$ticketsTable}.status", 'ilike', "%{$global}%")
                        ->orWhere("{$ticketsTable}.priority", 'ilike', "%{$global}%")
                        ->orWhere("{$ticketsTable}.category", 'ilike', "%{$global}%")
                        ->orWhereHas('tenant', function ($tenantQuery) use ($global) {
                            $tenantQuery->where('name', 'ilike', "%{$global}%")
                                ->orWhere('domain', 'ilike', "%{$global}%");
                        })
                        ->orWhereHas('createdBy', function ($userQuery) use ($global) {
                            $userQuery->where('name', 'ilike', "%{$global}%")
                                ->orWhere('email', 'ilike', "%{$global}%");
                        })
                        ->orWhereHas('assignedTo', function ($userQuery) use ($global) {
                            $userQuery->where('name', 'ilike', "%{$global}%")
                                ->orWhere('email', 'ilike', "%{$global}%");
                        });
                });
            })
            ->when($title !== '', fn($query) => $query->where("{$ticketsTable}.title", 'ilike', "%{$title}%"))
            ->when($status !== '', fn($query) => $query->where("{$ticketsTable}.status", $status))
            ->when($priority !== '', fn($query) => $query->where("{$ticketsTable}.priority", $priority))
            ->when($category !== '', fn($query) => $query->where("{$ticketsTable}.category", $category))
            ->when($tenant !== '', function ($query) use ($tenant) {
                $query->whereHas('tenant', function ($tenantQuery) use ($tenant) {
                    $tenantQuery->where('name', 'ilike', "%{$tenant}%")
                        ->orWhere('domain', 'ilike', "%{$tenant}%");
                });
            })
            ->when($createdBy !== '', function ($query) use ($createdBy) {
                $query->whereHas('createdBy', function ($userQuery) use ($createdBy) {
                    $userQuery->where('name', 'ilike', "%{$createdBy}%")
                        ->orWhere('email', 'ilike', "%{$createdBy}%");
                });
            })
            ->when($assignedTo !== '', function ($query) use ($assignedTo) {
                $query->whereHas('assignedTo', function ($userQuery) use ($assignedTo) {
                    $userQuery->where('name', 'ilike', "%{$assignedTo}%")
                        ->orWhere('email', 'ilike', "%{$assignedTo}%");
                });
            })
            ->when($createdAtInput !== '', fn($query) => $query->whereDate("{$ticketsTable}.created_at", $createdAtInput));

        return $query
            ->orderBy("{$ticketsTable}.{$sort}", $dir)
            ->paginate($perPage);
    }

    public function findOrFail(string $id): SupportTicket
    {
        return $this->baseQuery()
            ->whereKey($id)
            ->firstOrFail();
    }

    public function create(StoreSupportTicketRequest $req): SupportTicket
    {
        return DB::transaction(function () use ($req) {
            $data = $req->validated();

            $ticket = new SupportTicket();
            $ticket->tenant_id = $this->resolveCurrentTenantId();
            $ticket->created_by_id = auth()->id();
            $ticket->assigned_to_id = $this->resolveDefaultAssigneeId();
            $ticket->title = $data['title'];
            $ticket->description = $data['description'];
            $ticket->category = $data['category'] ?? SupportTicketCategory::OTHER->value;
            $ticket->priority = $data['priority'] ?? SupportTicketPriority::MEDIUM->value;
            $ticket->status = SupportTicketStatus::OPEN->value;
            $ticket->save();

            $this->storeAttachments($ticket, $req->file('attachments', []));

            return $ticket->refresh()->load([
                'tenant:id,name,domain',
                'createdBy:id,name,email',
                'assignedTo:id,name,email',
                'comments.user:id,name,email',
                'attachments',
            ]);
        });
    }

    public function update(SupportTicket $ticket, UpdateSupportTicketRequest $req): SupportTicket
    {
        return DB::transaction(function () use ($ticket, $req) {
            $scopedTicket = $this->findOrFail($ticket->id);
            $data = $req->validated();

            if (array_key_exists('title', $data)) {
                $scopedTicket->title = $data['title'];
            }

            if (array_key_exists('description', $data)) {
                $scopedTicket->description = $data['description'];
            }

            if (array_key_exists('category', $data)) {
                $scopedTicket->category = $data['category'] ?? SupportTicketCategory::OTHER->value;
            }

            if (array_key_exists('priority', $data)) {
                $scopedTicket->priority = $data['priority'] ?? SupportTicketPriority::MEDIUM->value;
            }

            $scopedTicket->save();

            return $scopedTicket->refresh()->load([
                'tenant:id,name,domain',
                'createdBy:id,name,email',
                'assignedTo:id,name,email',
                'comments.user:id,name,email',
                'attachments',
            ]);
        });
    }

    public function assign(SupportTicket $ticket, AssignSupportTicketRequest $req): SupportTicket
    {
        return DB::transaction(function () use ($ticket, $req) {
            $scopedTicket = $this->findOrFail($ticket->id);
            $data = $req->validated();

            $scopedTicket->assigned_to_id = $data['assigned_to_id'];
            $scopedTicket->save();

            return $scopedTicket->refresh()->load([
                'tenant:id,name,domain',
                'createdBy:id,name,email',
                'assignedTo:id,name,email',
                'comments.user:id,name,email',
                'attachments',
            ]);
        });
    }

    public function changeStatus(SupportTicket $ticket, ChangeSupportTicketStatusRequest $req): SupportTicket
    {
        return DB::transaction(function () use ($ticket, $req) {
            $scopedTicket = $this->findOrFail($ticket->id);
            $data = $req->validated();

            $status = $data['status'];

            $scopedTicket->status = $status;

            if ($status === SupportTicketStatus::RESOLVED->value) {
                $scopedTicket->resolved_at = now();
            }

            if ($status === SupportTicketStatus::CLOSED->value) {
                $scopedTicket->closed_at = now();
            }

            $scopedTicket->save();

            if (!empty($data['comment'])) {
                $this->createCommentFromArray($scopedTicket, [
                    'comment' => $data['comment'],
                    'is_internal' => false,
                ]);
            }

            return $scopedTicket->refresh()->load([
                'tenant:id,name,domain',
                'createdBy:id,name,email',
                'assignedTo:id,name,email',
                'comments.user:id,name,email',
                'attachments',
            ]);
        });
    }

    public function comment(SupportTicket $ticket, StoreSupportTicketCommentRequest $req): SupportTicket
    {
        return DB::transaction(function () use ($ticket, $req) {
            $scopedTicket = $this->findOrFail($ticket->id);
            $data = $req->validated();

            $comment = $this->createCommentFromArray($scopedTicket, $data);

            $this->storeAttachments($scopedTicket, $req->file('attachments', []), $comment->id);

            return $scopedTicket->refresh()->load([
                'tenant:id,name,domain',
                'createdBy:id,name,email',
                'assignedTo:id,name,email',
                'comments.user:id,name,email',
                'attachments',
            ]);
        });
    }

    public function delete(SupportTicket $ticket): void
    {
        DB::transaction(function () use ($ticket) {
            $scopedTicket = $this->findOrFail($ticket->id);

            foreach ($scopedTicket->attachments as $attachment) {
                if ($attachment->file_path && Storage::disk('public')->exists($attachment->file_path)) {
                    Storage::disk('public')->delete($attachment->file_path);
                }
            }

            Storage::disk('public')->deleteDirectory("support_tickets/{$scopedTicket->id}");

            $scopedTicket->delete();
        });
    }

    protected function createCommentFromArray(SupportTicket $ticket, array $data): SupportTicketComment
    {
        $comment = new SupportTicketComment();
        $comment->support_ticket_id = $ticket->id;
        $comment->user_id = auth()->id();
        $comment->comment = $data['comment'];
        $comment->is_internal = (bool)($data['is_internal'] ?? false);
        $comment->save();

        return $comment;
    }

    protected function storeAttachments(SupportTicket $ticket, array $files = [], ?string $commentId = null): void
    {
        foreach ($files as $file) {
            if (!$file) {
                continue;
            }

            $path = $file->store("support_tickets/{$ticket->id}", 'public');

            SupportTicketAttachment::create([
                'support_ticket_id' => $ticket->id,
                'support_ticket_comment_id' => $commentId,
                'uploaded_by_id' => auth()->id(),
                'file_path' => $path,
                'file_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'size' => $file->getSize(),
            ]);
        }
    }

    public function updateComment(
        SupportTicket $ticket,
        SupportTicketComment $comment,
        StoreSupportTicketCommentRequest $req
    ): SupportTicket {
        return DB::transaction(function () use ($ticket, $comment, $req) {
            $scopedTicket = $this->findOrFail($ticket->id);

            $scopedComment = $scopedTicket->comments()
                ->with('attachments')
                ->whereKey($comment->id)
                ->firstOrFail();

            $data = $req->validated();

            $scopedComment->comment = $data['comment'];
            $scopedComment->is_internal = (bool) ($data['is_internal'] ?? false);
            $scopedComment->save();

            $this->storeAttachments(
                $scopedTicket,
                $req->file('attachments', []),
                $scopedComment->id
            );

            return $scopedTicket->refresh()->load([
                'tenant:id,name,domain',
                'createdBy:id,name,email',
                'assignedTo:id,name,email',
                'comments.user:id,name,email',
                'comments.attachments',
                'attachments',
            ]);
        });
    }

    public function deleteComment(
        SupportTicket $ticket,
        SupportTicketComment $comment
    ): SupportTicket {
        return DB::transaction(function () use ($ticket, $comment) {
            $scopedTicket = $this->findOrFail($ticket->id);

            $scopedComment = $scopedTicket->comments()
                ->with('attachments')
                ->whereKey($comment->id)
                ->firstOrFail();

            foreach ($scopedComment->attachments as $attachment) {
                if ($attachment->file_path && Storage::disk('public')->exists($attachment->file_path)) {
                    Storage::disk('public')->delete($attachment->file_path);
                }

                $attachment->delete();
            }

            $scopedComment->delete();

            return $scopedTicket->refresh()->load([
                'tenant:id,name,domain',
                'createdBy:id,name,email',
                'assignedTo:id,name,email',
                'comments.user:id,name,email',
                'comments.attachments',
                'attachments',
            ]);
        });
    }

    public function addAttachments(SupportTicket $ticket, Request $req): SupportTicket
    {
        return DB::transaction(function () use ($ticket, $req) {
            $scopedTicket = $this->findOrFail($ticket->id);

            $this->storeAttachments($scopedTicket, $req->file('attachments', []));

            return $scopedTicket->refresh()->load([
                'tenant:id,name,domain',
                'createdBy:id,name,email',
                'assignedTo:id,name,email',
                'comments.user:id,name,email',
                'comments.attachments',
                'attachments',
            ]);
        });
    }

    public function deleteAttachment(
        SupportTicket $ticket,
        SupportTicketAttachment $attachment
    ): SupportTicket {
        return DB::transaction(function () use ($ticket, $attachment) {
            $scopedTicket = $this->findOrFail($ticket->id);

            $scopedAttachment = $scopedTicket->attachments()
                ->whereKey($attachment->id)
                ->first();

            if (!$scopedAttachment) {
                $scopedAttachment = SupportTicketAttachment::query()
                    ->where('support_ticket_id', $scopedTicket->id)
                    ->whereKey($attachment->id)
                    ->firstOrFail();
            }

            if ($scopedAttachment->file_path && Storage::disk('public')->exists($scopedAttachment->file_path)) {
                Storage::disk('public')->delete($scopedAttachment->file_path);
            }

            $scopedAttachment->delete();

            return $scopedTicket->refresh()->load([
                'tenant:id,name,domain',
                'createdBy:id,name,email',
                'assignedTo:id,name,email',
                'comments.user:id,name,email',
                'comments.attachments',
                'attachments',
            ]);
        });
    }
}
