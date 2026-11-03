<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Respuestas de verificación al reclamar un objeto (RF-09). */
class StoreReclamoRequest extends FormRequest
{
    use MensajesValidacion;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'respuesta1' => ['required', 'string', 'max:100'],
            'respuesta2' => ['required', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return $this->mensajesBase();
    }

    public function attributes(): array
    {
        return ['respuesta1' => 'respuesta 1', 'respuesta2' => 'respuesta 2'];
    }
}
