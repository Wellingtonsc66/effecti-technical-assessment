<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CpfCnpj implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $document = onlyNumbers($value);

        if (! $this->isValidCpf($document) && ! $this->isValidCnpj($document)) {
            $fail('O campo :attribute deve conter um CPF ou CNPJ válido.');
        }
    }

    private function isValidCpf(string $document): bool
    {
        if (strlen($document) !== 11 || preg_match('/^(\d)\1{10}$/', $document)) {
            return false;
        }

        for ($position = 9; $position < 11; $position++) {
            $sum = 0;

            for ($index = 0; $index < $position; $index++) {
                $sum += (int) $document[$index] * (($position + 1) - $index);
            }

            $digit = ((10 * $sum) % 11) % 10;

            if ((int) $document[$position] !== $digit) {
                return false;
            }
        }

        return true;
    }

    private function isValidCnpj(string $document): bool
    {
        if (strlen($document) !== 14 || preg_match('/^(\d)\1{13}$/', $document)) {
            return false;
        }

        $weights = [
            [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2],
            [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2],
        ];

        foreach ($weights as $position => $weightSet) {
            $sum = 0;

            foreach ($weightSet as $index => $weight) {
                $sum += (int) $document[$index] * $weight;
            }

            $remainder = $sum % 11;
            $digit = $remainder < 2 ? 0 : 11 - $remainder;

            if ((int) $document[12 + $position] !== $digit) {
                return false;
            }
        }

        return true;
    }
}
