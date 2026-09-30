<?php

namespace App\Policies;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TicketPolicy
{
    public function view(User $user, Ticket $ticket): Response
    {
        $column = $user->visibleTicketColumn();

        if ($column === null) {
            return Response::allow();
        }

        if ($ticket->{$column} !== null && (string) $ticket->{$column} === (string) $user->getKey()) {
            return Response::allow();
        }

        return Response::deny(
            $user->isManager()
                ? 'Gestores só veem os chamados sob sua responsabilidade.'
                : 'Você só vê os chamados que abriu.'
        );
    }

    public function update(User $user, Ticket $ticket): Response
    {
        if ($user->isManager()) {
            return Response::allow();
        }

        // O usuário comum mexe no próprio chamado, e apenas enquanto ele está
        // aberto. Depois que começa o atendimento, quem conduz o chamado é o
        // responsável.
        if ((string) $ticket->user_id !== (string) $user->getKey()) {
            return Response::deny('Você só pode editar os chamados que abriu.');
        }

        if ($ticket->status !== TicketStatus::OPEN->value) {
            return Response::deny('Você só pode editar o chamado enquanto ele estiver aberto.');
        }

        return Response::allow();
    }

    public function delete(User $user, Ticket $ticket): Response
    {
        return $user->isManager()
            ? Response::allow()
            : Response::deny('Apenas gestores e administradores podem excluir chamados.');
    }

    public function balance(User $user): Response
    {
        return $user->isAdmin()
            ? Response::allow()
            : Response::deny('Apenas administradores podem redistribuir os chamados.');
    }

    public function unassignOpen(User $user): Response
    {
        return $user->isAdmin()
            ? Response::allow()
            : Response::deny('Apenas administradores podem remover os responsáveis.');
    }
}
