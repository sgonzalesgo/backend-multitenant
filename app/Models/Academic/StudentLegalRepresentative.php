<?php

namespace App\Models\Academic;

use App\Models\Administration\Tenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentLegalRepresentative extends Pivot
{
    use HasUuids, SoftDeletes;

    protected $table = 'student_legal_representatives';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'tenant_id',
        'student_id',
        'legal_representative_id',
        'relationship_type',
        'description',
        'is_billable',
        'is_emergency_contact',
    ];

    protected $casts = [
        'is_billable' => 'boolean',
        'is_emergency_contact' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function legalRepresentative(): BelongsTo
    {
        return $this->belongsTo(LegalRepresentative::class);
    }
}
