<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Validación del registro de un objeto encontrado (RF-03, RN-03, RNF-03). */
class StoreObjetoRequest extends FormRequest
{
    use MensajesValidacion;

    public function authorize(): bool
    {
        // Los roles se controlan en la ruta (middleware can:gestionar); aquí se deja en true.
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'descripcion' => trim((string) $this->descripcion),
            'color' => trim((string) $this->color),
        ]);
    }

    public function rules(): array
    {
        return [
            'categoria_id' => ['required', 'integer', Rule::exists('categorias', 'id')->where('activo', true)],
            'lugar_id' => ['required', 'integer', Rule::exists('lugares', 'id')->where('activo', true)],
            'descripcion' => ['required', 'string', 'min:10', 'max:500'],
            'color' => ['required', 'string', 'min:3', 'max:40', 'regex:/^[\pL\s,\/-]+$/u'],
            'fecha_hallazgo' => ['required', 'date', 'before_or_equal:today', 'after_or_equal:' . now()->subYear()->toDateString()],
            'foto' => $this->reglasFoto(true),
            'pregunta1' => ['required', 'string', 'min:5', 'max:200'],
            'respuesta1' => ['required', 'string', 'min:1', 'max:100'],
            'pregunta2' => ['required', 'string', 'min:5', 'max:200', 'different:pregunta1'],
            'respuesta2' => ['required', 'string', 'min:1', 'max:100'],
        ];
    }

    protected function reglasFoto(bool $obligatoria): array
    {
        return [$obligatoria ? 'required' : 'nullable', 'file', 'image', 'mimes:jpg,jpeg,png', 'max:5120', 'dimensions:min_width=200,min_height=200'];
    }

    public function messages(): array
    {
        return $this->mensajesBase() + [
            'color.regex' => 'El color solo puede tener letras (por ejemplo: azul, negro con rojo).',
            'foto.required' => 'La foto del objeto es obligatoria.',
            'foto.image' => 'El archivo debe ser una imagen.',
            'foto.mimes' => 'Solo se aceptan fotos JPG o PNG.',
            'foto.max' => 'La foto no debe superar los 5 MB.',
            'foto.dimensions' => 'La foto debe medir al menos 200 × 200 píxeles.',
            'pregunta2.different' => 'Las dos preguntas de verificación deben ser diferentes.',
            'fecha_hallazgo.before_or_equal' => 'La fecha de hallazgo no puede ser futura.',
            'fecha_hallazgo.after_or_equal' => 'La fecha de hallazgo no puede tener más de un año de antigüedad.',
        ];
    }

    public function attributes(): array
    {
        return [
            'categoria_id' => 'categoría',
            'lugar_id' => 'lugar donde se encontró',
            'descripcion' => 'descripción',
            'color' => 'color',
            'fecha_hallazgo' => 'fecha de hallazgo',
            'foto' => 'foto del objeto',
            'pregunta1' => 'pregunta de verificación 1',
            'respuesta1' => 'respuesta 1',
            'pregunta2' => 'pregunta de verificación 2',
            'respuesta2' => 'respuesta 2',
        ];
    }
}
