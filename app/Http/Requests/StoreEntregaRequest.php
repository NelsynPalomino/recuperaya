<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Registro de la entrega del objeto (RF-11, RN-05). */
class StoreEntregaRequest extends FormRequest
{
    use MensajesValidacion;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['codigo_carne' => strtoupper(trim((string) $this->codigo_carne))]);
    }

    public function rules(): array
    {
        return [
            'reclamo_id' => ['required', 'integer', Rule::exists('reclamos', 'id')->where('estado', 'Aprobado')],
            'codigo_carne' => ['required', 'alpha_num', 'between:6,20'],
            'carne_verificado' => ['accepted'],
            'observacion' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return $this->mensajesBase() + [
            'reclamo_id.exists' => 'Solo se pueden entregar objetos con reclamo aprobado.',
            'carne_verificado.accepted' => 'Debes verificar el carné universitario antes de entregar el objeto.',
        ];
    }

    public function attributes(): array
    {
        return [
            'reclamo_id' => 'reclamo',
            'codigo_carne' => 'código del carné',
            'carne_verificado' => 'verificación del carné',
            'observacion' => 'observación',
        ];
    }
}
