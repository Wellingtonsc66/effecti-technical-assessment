<?php

namespace App\Http\Requests;

use App\Enums\ContractStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ContractRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'client_id' => ['required', 'exists:clients,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['required', Rule::enum(ContractStatusEnum::class)],
        ];
    }
}
