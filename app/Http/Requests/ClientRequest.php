<?php

namespace App\Http\Requests;

use App\Enums\ClientStatusEnum;
use App\Rules\CpfCnpj;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClientRequest extends FormRequest
{
    public function rules(): array
    {
        $client = $this->route('client');

        return [
            'name' => ['required', 'string', 'max:255'],
            'document' => [
                'required',
                'string',
                'max:20',
                new CpfCnpj,
                Rule::unique('clients', 'document')->ignore($client),
            ],
            'email' => [
                'required',
                'email:rfc',
                'regex:/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/',
                'max:255',
                Rule::unique('clients', 'email')->ignore($client),
            ],
            'status' => ['required', Rule::enum(ClientStatusEnum::class)],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'document' => preg_replace('/\D/', '', (string) $this->input('document')),
        ]);
    }

    public function attributes(): array
    {
        return [
            'name' => 'nome',
            'document' => 'CPF ou CNPJ',
            'email' => 'e-mail',
            'status' => 'status',
        ];
    }
}
