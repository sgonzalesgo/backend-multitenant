<?php

namespace App\Enums\Support;

enum SupportTicketStatus: string
{
    case OPEN = 'open';
    case IN_PROGRESS = 'in_progress';
    case WAITING_USER = 'waiting_user';
    case RESOLVED = 'resolved';
    case CLOSED = 'closed';
    case REJECTED = 'rejected';
}
