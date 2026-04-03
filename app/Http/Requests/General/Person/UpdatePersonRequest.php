<?php

namespace App\Http\Requests\General\Person;

use App\Models\General\Person;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdatePersonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Person $person */
        $person = $this->route('person');

        return [
            // Person
            'full_name' => ['sometimes', 'required', 'string', 'max:255'],
            'photo' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:120'],
            'country' => ['nullable', 'string', 'max:120'],
            'zip' => ['nullable', 'string', 'max:30'],
            'legal_id' => ['sometimes', 'required', 'string', 'max:50'],
            'legal_id_type' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('persons')
                    ->ignore($person->id)
                    ->where(function ($query) use ($person) {
                        return $query->where('legal_id', $this->input('legal_id', $person->legal_id));
                    }),
            ],
            'birthday' => ['nullable', 'date'],
            'gender' => ['nullable', 'string', 'max:30'],
            'marital_status' => ['nullable', 'string', 'max:30'],
            'blood_group' => ['nullable', 'string', 'max:10'],
            'nationality' => ['nullable', 'string', 'max:120'],
            'deceased_at' => ['nullable', 'date'],
            'status_changed_at' => ['nullable', 'date'],

            // User
            'has_user' => ['nullable', 'boolean'],
            'user_name' => ['nullable', 'required_if:has_user,true,1', 'string', 'max:255'],
            'user_email' => [
                'nullable',
                'required_if:has_user,true,1',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore(optional($person->user)->id),
            ],
            'user_password' => ['nullable', 'confirmed', Password::defaults()],
            'user_status' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        $messages = trans('validation/general/person.custom');

        return is_array($messages) ? $messages : [];
    }

    public function attributes(): array
    {
        $attributes = trans('validation/general/person.attributes');

        return is_array($attributes) ? $attributes : [];
    }
}
