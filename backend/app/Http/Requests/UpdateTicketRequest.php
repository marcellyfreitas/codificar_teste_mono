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
        $rules = [
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string'],
            'priority' => ['sometimes', new Enum(TicketPriority::class)],
            'status' => ['sometimes', new Enum(TicketStatus::class)],
            'assignee_id' => ['nullable', 'integer', new ExistsAsGestor],
        ];

        // A policy já libera o usuário comum a editar o próprio chamado
        // aberto. O status fica de fora: editar o conteúdo do pedido não é a
        // mesma coisa que conduzir o atendimento, e quem conduz é o gestor.
        if (! $this->user()?->isManager()) {
            $rules['status'] = ['prohibited'];
        }

        return $rules;
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
            'prohibited' => 'O campo :attribute só pode ser alterado por gestores e administradores.',
            'Illuminate\Validation\Rules\Enum' => 'O valor fornecido para :attribute é inválido.',
        ];
    }
}
