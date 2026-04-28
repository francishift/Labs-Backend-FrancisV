<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMantenimientoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'aplicacion' => 'required|string|max:255',
            'url' => 'nullable|url|max:255',
            'client_id' => 'required|exists:clients,id',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
            'tipo_pago' => 'required|string|in:mensual,trimestral,anual',
            'importe' => 'required|numeric|min:0',
            'estado' => 'required|string|in:en curso,finalizado',
            'descripcion' => 'nullable|string',
            'extensiones' => 'nullable|array',
            'extensiones.*' => 'exists:extensiones,id',
        ];
    }
}
