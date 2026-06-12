<?php

namespace App\Models\Support;

use App\Models\Administration\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportTicketAttachment extends Model
{
    use HasUuids;

    protected $fillable = [
        'support_ticket_id',
        'support_ticket_comment_id',
        'uploaded_by_id',
        'file_path',
        'file_name',
        'mime_type',
        'size',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'support_ticket_id');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_id');
    }

    public function comment(): BelongsTo
    {
        return $this->belongsTo(SupportTicketComment::class, 'support_ticket_comment_id');
    }
}
