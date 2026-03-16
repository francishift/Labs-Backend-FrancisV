<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreNotaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('hora') && strlen($this->hora) > 5) {
            $this->merge(['hora' => substr($this->hora, 0, 5)]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'fecha' => 'required|date',
            'hora' => 'required|date_format:H:i',
            'comentario' => 'required|string',
            'enlace_reunion' => 'nullable|url',
            'notificacion_minutos_antes' => 'required|integer|min:-1',
            'sync_calendar' => 'boolean',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->notificacion_minutos_antes != -1) {
                // Asegurar que fecha y hora existen en la petición antes de parsear para evitar errores nulos
                if ($this->fecha && $this->hora) {
                    $notaDateTime = \Carbon\Carbon::parse($this->fecha . ' ' . $this->hora);
                    if ($notaDateTime->isPast()) {
                        $validator->errors()->add('fecha', 'La fecha no puede estar en el pasado si hay notificación.');
                        $validator->errors()->add('hora', 'La hora no puede estar en el pasado si hay notificación.');
                    }
                }
            }
        });
    }
}
