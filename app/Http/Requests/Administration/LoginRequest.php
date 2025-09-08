<?php

namespace App\Http\Requests\Administration;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // si necesitas permisos, colócalo aquí
    }

    public function rules(): array
    {
        return [
            'email'    => ['required','email'],
            'password' => ['required','string','min:6'],
        ];
    }

    // (Opcional) Sanea el input antes de validar
    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => $this->email ? trim(strtolower($this->email)) : $this->email,
        ]);
    }

    /**
     * Si prefieres definir mensajes aquí en vez de lang/validation.php,
     * puedes usar __() para i18n. (yo recomiendo mantenerlos en lang).
     */
    // public function messages(): array
    // {
    //     return [
    //         'email.required'    => __('validation.required'),
    //         'email.email'       => __('validation.email'),
    //         'password.required' => __('validation.required'),
    //         'password.min'      => __('validation.min.string', ['min' => 6]),
    //     ];
    // }

    // Mapea nombres de atributos amigables (también puedes ponerlo en lang/validation.php -> attributes)
    public function attributes(): array
    {
        return [
            'email'    => __('validation.email'),
            'password' => __('validation.password'),
        ];
    }
}
