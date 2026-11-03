<?php

namespace App\Http\Requests;

/** Edición de un objeto: igual que el registro, pero la foto y las respuestas son opcionales. */
class UpdateObjetoRequest extends StoreObjetoRequest
{
    public function rules(): array
    {
        $reglas = parent::rules();
        $reglas['foto'] = $this->reglasFoto(false);
        $reglas['respuesta1'] = ['nullable', 'string', 'max:100', 'required_with:respuesta2'];
        $reglas['respuesta2'] = ['nullable', 'string', 'max:100', 'required_with:respuesta1'];
        return $reglas;
    }

    public function messages(): array
    {
        return parent::messages() + ['required_with' => 'Si cambias una respuesta, escribe también la otra.'];
    }
}
