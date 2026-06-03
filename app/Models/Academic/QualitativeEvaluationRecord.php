<?php

namespace App\Models\Academic;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QualitativeEvaluationRecord extends Model
{
    use HasUuids;

    protected $table = 'qualitative_evaluation_records';

    protected $fillable = [
        'tenant_id',
        'qualitative_evaluation_session_id',
        'student_id',
        'enrollment_id',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(
            QualitativeEvaluationSession::class,
            'qualitative_evaluation_session_id'
        );
    }

    public function skills(): HasMany
    {
        return $this->hasMany(
            QualitativeEvaluationRecordSkill::class,
            'qualitative_evaluation_record_id'
        );
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class, 'enrollment_id');
    }
}
