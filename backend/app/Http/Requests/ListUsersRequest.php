<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class ListUsersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'role' => ['sometimes', 'nullable', new Enum(UserRole::class)],
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'role' => 'papel',
            'search' => 'busca',
        ];
    }

    public function messages(): array
    {
        return [
            'string' => 'O campo :attribute deve ser um texto.',
            'max' => 'O campo :attribute não pode ter mais que :max caracteres.',
            'Illuminate\Validation\Rules\Enum' => 'O valor fornecido para :attribute é inválido.',
        ];
    }
}
