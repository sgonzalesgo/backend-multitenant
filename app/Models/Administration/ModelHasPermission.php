<?php

namespace App\Models\Administration;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ModelHasPermission extends Model
{
    protected $table = 'model_has_permissions';

    public $timestamps = false;
    public $incrementing = false;

    protected $guarded = [];

    public function model(): MorphTo
    {
        return $this->morphTo();
    }
}
