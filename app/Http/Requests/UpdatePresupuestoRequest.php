<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePresupuestoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_id' => 'required|exists:clients,id',
            'date' => 'required|date',
            'notes' => 'nullable|string',
            'lineas' => 'required|array|min:1',
            'lineas.*.concepto' => 'required|string',
            'lineas.*.descripcion' => 'nullable|string',
            'lineas.*.cantidad' => 'required|numeric|min:0.01',
            'lineas.*.precio_unitario' => 'required|numeric',
            'lineas.*.porcentaje_iva' => 'required|numeric|min:0',
            'lineas.*.porcentaje_irpf' => 'required|numeric|min:0',
            'due_date' => 'nullable|date',
            'status' => 'nullable|integer',
            'description' => 'nullable|string',
        ];
    }
}
