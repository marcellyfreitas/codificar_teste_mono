<?php

namespace App\Http\Requests;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Rules\ExistsAsGestor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'priority' => ['required', new Enum(TicketPriority::class)],
            'status' => ['nullable', new Enum(TicketStatus::class)],
            'assignee_id' => ['nullable', 'integer', new ExistsAsGestor],
            'auto_assign' => ['sometimes', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'título',
            'description' => 'descrição',
            'priority' => 'prioridade',
            'status' => 'status',
            'assignee_id' => 'responsável',
            'auto_assign' => 'atribuição automática',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'O campo :attribute é obrigatório.',
            'max' => 'O campo :attribute não pode ter mais que :max caracteres.',
            'boolean' => 'O campo :attribute deve ser verdadeiro ou falso.',
            'Illuminate\Validation\Rules\Enum' => 'O valor fornecido para :attribute é inválido.',
        ];
    }
}
