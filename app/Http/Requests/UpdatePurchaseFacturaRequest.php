<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use App\Enums\PurchaseFacturaStatus;

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
            'status' => ['required', new Enum(PurchaseFacturaStatus::class)],
            'notes' => 'nullable|string',
        ];
    }
}
