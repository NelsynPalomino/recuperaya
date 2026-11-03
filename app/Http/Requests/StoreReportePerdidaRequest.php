<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Validación del reporte de pérdida (RF-05). */
class StoreReportePerdidaRequest extends FormRequest
{
    use MensajesValidacion;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'lugar_id' => $this->lugar_id === '' ? null : $this->lugar_id,
            'descripcion' => trim((string) $this->descripcion),
            'color' => trim((string) $this->color),
        ]);
    }

    public function rules(): array
    {
        return [
            'categoria_id' => ['required', 'integer', Rule::exists('categorias', 'id')->where('activo', true)],
            'lugar_id' => ['nullable', 'integer', Rule::exists('lugares', 'id')->where('activo', true)],
            'descripcion' => ['required', 'string', 'min:10', 'max:500'],
            'color' => ['required', 'string', 'min:3', 'max:40', 'regex:/^[\pL\s,\/-]+$/u'],
            'fecha_perdida' => ['required', 'date', 'before_or_equal:today', 'after_or_equal:' . now()->subYear()->toDateString()],
        ];
    }

    public function messages(): array
    {
        return $this->mensajesBase() + [
            'descripcion.min' => 'Describe tu objeto con al menos 10 caracteres.',
            'color.regex' => 'El color solo puede tener letras.',
            'fecha_perdida.before_or_equal' => 'La fecha de pérdida no puede ser futura.',
        ];
    }

    public function attributes(): array
    {
        return [
            'categoria_id' => 'qué perdiste',
            'lugar_id' => 'lugar donde lo perdiste',
            'descripcion' => 'descripción',
            'color' => 'color',
            'fecha_perdida' => 'fecha de pérdida',
        ];
    }
}
