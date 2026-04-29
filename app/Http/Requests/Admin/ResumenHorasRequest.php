<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ResumenHorasRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Asumimos que la ruta ya está protegida por auth/admin middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'year' => ['nullable', 'integer', 'min:2020', 'max:' . (date('Y') + 5)],
            'client_id' => ['nullable', 'integer', 'exists:clients,id'],
            'tipo_servicio' => ['nullable', 'string', 'in:proyectos,mantenimientos'],
        ];
    }

    public function prepareForValidation()
    {
        if ($this->has('year') && empty($this->year)) {
            $this->merge(['year' => now()->year]);
        }
    }
}
