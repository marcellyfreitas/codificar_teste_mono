<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TicketPolicy
{
    public function update(User $user, Ticket $ticket): Response
    {
        return $user->isManager()
            ? Response::allow()
            : Response::deny('Apenas gestores e administradores podem editar chamados.');
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
