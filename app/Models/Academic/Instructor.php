<?php

namespace App\Models\Academic;

use App\Models\General\Department;
use App\Models\General\Person;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Instructor extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'instructors';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'person_id',
        'department_id',
        'academic_title',
        'academic_level',
        'status',
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
