<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SendFacturaEmailRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            'cc_emails' => ['nullable', 'string', 'max:500'],
            'send_copy_to_me' => ['boolean'],
            'message' => ['nullable', 'string', 'max:2000'],
        ];
    }
    
    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'email' => 'correo del destinatario',
            'cc_emails' => 'correos en copia (CC)',
            'send_copy_to_me' => 'enviarme copia a mí mismo',
            'message' => 'mensaje',
        ];
    }
}
