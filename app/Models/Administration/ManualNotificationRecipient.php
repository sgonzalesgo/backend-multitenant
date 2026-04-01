<?php

namespace App\Models\Administration;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManualNotificationRecipient extends Model
{
    use HasUuids;

    protected $table = 'manual_notification_recipients';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'manual_notification_id',
        'user_id',
        'notification_id',
    ];

    public function manualNotification(): BelongsTo
    {
        return $this->belongsTo(ManualNotification::class, 'manual_notification_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function notification(): BelongsTo
    {
        return $this->belongsTo(Notification::class, 'notification_id');
    }
}
