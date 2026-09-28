<?php

namespace App\Http\Requests;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Rules\ExistsAsGestor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string'],
            'priority' => ['sometimes', new Enum(TicketPriority::class)],
            'status' => ['sometimes', new Enum(TicketStatus::class)],
            'assignee_id' => ['nullable', 'integer', new ExistsAsGestor],
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
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'O campo :attribute é obrigatório.',
            'max' => 'O campo :attribute não pode ter mais que :max caracteres.',
            'Illuminate\Validation\Rules\Enum' => 'O valor fornecido para :attribute é inválido.',
        ];
    }
}
