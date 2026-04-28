<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMantenimientoServicioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mantenimiento_id' => 'required|exists:mantenimientos,id',
            'descripcion' => 'required|string',
            'duracion_minutos' => 'required|integer|min:0',
            'fecha' => 'required|date',
        ];
    }
}
