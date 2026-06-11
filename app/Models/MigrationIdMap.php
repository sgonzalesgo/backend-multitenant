<?php

namespace App\Models;

use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Model;

class MigrationIdMap extends Model
{
    use Uuid;

    protected $fillable = [
        'entity',
        'old_id',
        'new_id',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];
}
