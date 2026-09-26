<?php

namespace App\Http\Requests;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class ListTicketsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'nullable', new Enum(TicketStatus::class)],
            'priority' => ['sometimes', 'nullable', new Enum(TicketPriority::class)],
            'user_id' => ['sometimes', 'nullable'],
            'assignee_id' => ['sometimes', 'nullable'],
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'created_from' => ['sometimes', 'nullable', 'string'],
            'created_to' => ['sometimes', 'nullable', 'string'],
            'per_page' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function attributes(): array
    {
        return [
            'status' => 'status',
            'priority' => 'prioridade',
            'user_id' => 'autor',
            'assignee_id' => 'responsável',
            'search' => 'busca',
            'created_from' => 'data inicial',
            'created_to' => 'data final',
            'per_page' => 'quantidade por página',
        ];
    }

    public function messages(): array
    {
        return [
            'string' => 'O campo :attribute deve ser um texto.',
            'max' => 'O campo :attribute não pode ter mais que :max caracteres.',
            'integer' => 'O campo :attribute deve ser um número inteiro.',
            'min' => 'O campo :attribute deve ser no mínimo :min.',
            'Illuminate\Validation\Rules\Enum' => 'O valor fornecido para :attribute é inválido.',
        ];
    }

    public function filters(): array
    {
        return [
            'status' => $this->input('status'),
            'priority' => $this->input('priority'),
            'user_id' => $this->input('user_id'),
            'assignee_id' => $this->input('assignee_id'),
            'search' => $this->input('search'),
            'created_from' => $this->input('created_from'),
            'created_to' => $this->input('created_to'),
        ];
    }

    public function perPage(): int
    {
        return (int) $this->input('per_page', 15);
    }
}
