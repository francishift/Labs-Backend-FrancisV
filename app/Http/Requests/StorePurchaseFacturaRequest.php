<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseFacturaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'mimes:pdf',
                function ($attribute, $value, $fail) {
                    if ($value->getSize() > 10485760) {
                        $fail('El archivo no debe pesar más de 10 MB.');
                    }
                },
            ],
        ];
    }
}
