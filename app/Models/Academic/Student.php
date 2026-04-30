<?php

namespace App\Models\Academic;

use App\Models\Administration\Tenant;
use App\Models\General\Person;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'students';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'tenant_id',
        'person_id',
        'student_code',
        'status',
        'notes',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
