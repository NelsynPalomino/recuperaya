<?php

namespace App\Http\Requests;

/**
 * Mensajes en español compartidos por todos los Form Requests.
 * Cada Form Request puede agregar mensajes propios con la sintaxis 'campo.regla' => 'mensaje'.
 */
trait MensajesValidacion
{
    protected function mensajesBase(): array
    {
        return [
            'required' => 'El campo :attribute es obligatorio.',
            'string' => 'El campo :attribute debe ser texto.',
            'integer' => 'El campo :attribute debe ser un número entero.',
            'boolean' => 'El campo :attribute debe ser Sí o No.',
            'email' => 'El :attribute debe ser un correo electrónico válido.',
            'file' => 'El campo :attribute debe ser un archivo.',
            // Las reglas de tamaño se definen por tipo de dato (texto, número, archivo, lista):
            'min' => [
                'string' => 'El campo :attribute debe tener al menos :min caracteres.',
                'numeric' => 'El campo :attribute debe ser como mínimo :min.',
                'file' => 'El archivo :attribute debe pesar al menos :min KB.',
                'array' => 'El campo :attribute debe tener al menos :min elementos.',
            ],
            'max' => [
                'string' => 'El campo :attribute no debe superar :max caracteres.',
                'numeric' => 'El campo :attribute no debe ser mayor que :max.',
                'file' => 'El archivo :attribute no debe superar :max KB.',
                'array' => 'El campo :attribute no debe tener más de :max elementos.',
            ],
            'between' => [
                'string' => 'El campo :attribute debe tener entre :min y :max caracteres.',
                'numeric' => 'El campo :attribute debe estar entre :min y :max.',
                'file' => 'El archivo :attribute debe pesar entre :min y :max KB.',
                'array' => 'El campo :attribute debe tener entre :min y :max elementos.',
            ],
            'exists' => 'El valor seleccionado en :attribute no es válido.',
            'unique' => 'El :attribute ya está registrado.',
            'date' => 'El campo :attribute debe ser una fecha válida.',
            'date_format' => 'El campo :attribute debe tener el formato día/mes/año.',
            'before_or_equal' => 'La :attribute no puede ser una fecha futura.',
            'after_or_equal' => 'La :attribute no puede tener más de un año de antigüedad.',
            'in' => 'El valor seleccionado en :attribute no es válido.',
            'regex' => 'El formato del campo :attribute no es válido.',
            'alpha_num' => 'El campo :attribute solo puede tener letras y números.',
            'accepted' => 'Debes marcar la casilla: :attribute.',
            'confirmed' => 'La confirmación de :attribute no coincide.',
            'different' => 'El campo :attribute debe ser diferente de :other.',
        ];
    }
}
