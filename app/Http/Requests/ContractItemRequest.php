<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContractItemRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'service_id' => ['required', 'exists:services,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:999999'],
            'unit_value' => ['nullable', 'numeric', 'min:0.01', 'max:99999999.99'],
        ];
    }
}
