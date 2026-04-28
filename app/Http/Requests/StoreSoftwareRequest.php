<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSoftwareRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tipo' => 'required|in:Software,Hosting',
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'tipo_licencia' => 'required|in:Anual,Mensual',
            'precio' => 'required|numeric|min:0',
            'estado' => 'required|in:Activa,Finalizada',
        ];
    }
}
