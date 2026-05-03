<?php

namespace App\Models\Academic;

use App\Models\Administration\Tenant;
use App\Models\General\Person;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LegalRepresentative extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'legal_representatives';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'tenant_id',
        'person_id',
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

    public function studentRelationships(): HasMany
    {
        return $this->hasMany(StudentLegalRepresentative::class);
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(
            Student::class,
            'student_legal_representatives',
            'legal_representative_id',
            'student_id'
        )
            ->using(StudentLegalRepresentative::class)
            ->withPivot([
                'id',
                'tenant_id',
                'relationship_type',
                'description',
                'is_billable',
                'is_emergency_contact',
                'deleted_at',
            ])
            ->withTimestamps();
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
