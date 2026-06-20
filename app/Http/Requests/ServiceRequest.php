<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ServiceRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'monthly_base_value' => ['required', 'numeric', 'min:0.01', 'max:99999999.99'],
        ];
    }
}
