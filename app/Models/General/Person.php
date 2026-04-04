<?php

namespace App\Models\General;

use App\Models\Administration\User;
use App\Models\General\Country;
use App\Models\General\State;
use App\Models\General\City;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'country_id',
        'state_id',
        'city_id',
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
        'birthday' => 'date',
        'deceased_at' => 'datetime',
        'status_changed_at' => 'datetime',
    ];

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
}
