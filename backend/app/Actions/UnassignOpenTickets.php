<?php

namespace App\Actions;

use App\Services\TicketService;

class UnassignOpenTickets
{
    public function __construct(
        private TicketService $ticketService,
    ) {}

    public function __invoke(): int
    {
        return $this->ticketService->unassignOpenTickets();
    }
}
