<?php


namespace App\Enums\Support;

enum SupportTicketCategory: string
{
    case BUG = 'bug';
    case QUESTION = 'question';
    case IMPROVEMENT = 'improvement';
    case ACCESS = 'access';
    case OTHER = 'other';
}
