<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Validación de categorías y lugares (RF-13). Sirve para crear y para editar. */
class StoreCatalogoRequest extends FormRequest
{
    use MensajesValidacion;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['nombre' => trim((string) $this->nombre), 'activo' => $this->boolean('activo')]);
    }

    public function rules(): array
    {
        $tabla = $this->route('lugar') !== null || $this->routeIs('lugares.*') ? 'lugares' : 'categorias';
        $modelo = $this->route('lugar') ?? $this->route('categoria');
        return [
            'nombre' => ['required', 'string', 'min:2', 'max:60', Rule::unique($tabla, 'nombre')->ignore($modelo?->id)],
            'activo' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return $this->mensajesBase() + ['nombre.unique' => 'Ese nombre ya existe.'];
    }

    public function attributes(): array
    {
        return ['nombre' => 'nombre', 'activo' => 'activo'];
    }
}
