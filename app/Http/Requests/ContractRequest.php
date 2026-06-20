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

    public function messages(): array
    {
        return [
            'client_id.exists' => 'O cliente selecionado é inválido.',
            'end_date.after_or_equal' => 'A data de término deve ser posterior ou igual à data de início.',
        ];
    }

    public function attributes(): array
    {
        return [
            'client_id'  => 'cliente',
            'start_date' => 'data de início',
            'end_date'   => 'data de término',
            'status'     => 'status',
        ];
    }
}
