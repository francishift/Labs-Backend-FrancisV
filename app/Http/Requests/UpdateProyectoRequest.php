<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProyectoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'proyecto' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required_if:estado,Finalizado|nullable|date|after_or_equal:fecha_inicio',
            'presupuesto' => 'nullable|numeric|min:0',
            'estado' => 'required|string|in:En proceso,Finalizado,Cancelado',
            'client_id' => 'required|exists:clients,id',
            'presupuesto_id' => 'nullable|exists:presupuestos,id',
            'extensiones' => 'nullable|array',
            'extensiones.*' => 'exists:extensiones,id',
            'facturas' => 'nullable|array',
            'facturas.*' => 'exists:facturas,id',
        ];
    }
}
