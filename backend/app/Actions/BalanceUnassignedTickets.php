<?php

namespace App\Actions;

use App\Services\TicketService;

class BalanceUnassignedTickets
{
    public function __construct(
        private TicketService $ticketService,
    ) {}

    public function __invoke(): array
    {
        return $this->ticketService->balanceUnassignedTickets();
    }
}
