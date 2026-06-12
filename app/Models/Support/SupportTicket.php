<?php

namespace App\Models\Support;

use App\Models\Administration\User;
use App\Models\Administration\Tenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use App\Enums\Support\SupportTicketCategory;
use App\Enums\Support\SupportTicketPriority;
use App\Enums\Support\SupportTicketStatus;

class SupportTicket extends Model
{
    use HasUuids;

    protected $fillable = [
        'tenant_id',
        'created_by_id',
        'assigned_to_id',
        'title',
        'description',
        'category',
        'priority',
        'status',
        'resolved_at',
        'closed_at',
    ];

    protected $casts = [
        'category' => SupportTicketCategory::class,
        'priority' => SupportTicketPriority::class,
        'status' => SupportTicketStatus::class,
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(SupportTicketComment::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(SupportTicketAttachment::class);
    }
}
