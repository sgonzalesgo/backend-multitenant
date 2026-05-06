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

    public function legalRepresentativeRelationships(): HasMany
    {
        return $this->hasMany(StudentLegalRepresentative::class);
    }

    public function legalRepresentatives(): BelongsToMany
    {
        return $this->belongsToMany(
            LegalRepresentative::class,
            'student_legal_representatives',
            'student_id',
            'legal_representative_id'
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

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }
}
