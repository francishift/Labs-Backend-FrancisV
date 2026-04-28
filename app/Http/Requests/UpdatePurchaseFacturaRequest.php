<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePurchaseFacturaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'number' => 'required|string|unique:purchase_facturas,number,' . $this->route('purchaseFactura')->id,
            'provider_name' => 'required|string',
            'date' => 'required|date',
            'total' => 'required|numeric',
            'net_amount' => 'nullable|numeric',
            'tax_amount' => 'nullable|numeric',
            'irpf_amount' => 'nullable|numeric',
            'status' => 'required|string',
            'notes' => 'nullable|string',
        ];
    }
}
