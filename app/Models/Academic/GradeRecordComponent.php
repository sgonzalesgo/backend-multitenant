<?php

namespace App\Models\Academic;

use App\Models\Administration\Tenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GradeRecordComponent extends Model
{
    use HasUuids;

    protected $table = 'grade_record_components';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'tenant_id',
        'grade_record_id',
        'grade_component_id',
        'score',
        'qualitative_grade',
        'observation',
    ];

    protected $casts = [
        'score' => 'decimal:2',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function gradeRecord(): BelongsTo
    {
        return $this->belongsTo(GradeRecord::class);
    }

    public function gradeComponent(): BelongsTo
    {
        return $this->belongsTo(GradeComponent::class);
    }
}
