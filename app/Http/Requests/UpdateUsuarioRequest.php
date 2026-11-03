<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Cambio de rol y estado de un usuario por el administrador (RF-02). */
class UpdateUsuarioRequest extends FormRequest
{
    use MensajesValidacion;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rol' => ['required', Rule::in([User::USUARIO, User::ENCARGADO, User::ADMIN])],
            'activo' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return $this->mensajesBase();
    }

    public function attributes(): array
    {
        return ['rol' => 'rol', 'activo' => 'estado de la cuenta'];
    }
}
