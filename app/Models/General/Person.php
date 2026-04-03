<?php

namespace App\Models\General;

use App\Models\Administration\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Person extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'persons';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'full_name',
        'photo',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'country',
        'zip',
        'legal_id',
        'legal_id_type',
        'birthday',
        'gender',
        'marital_status',
        'blood_group',
        'nationality',
        'deceased_at',
        'status_changed_at',
    ];

    protected $casts = [
        'birthday' => 'date'
    ];

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }
}
