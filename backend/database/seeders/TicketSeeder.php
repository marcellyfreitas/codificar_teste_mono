<?php

namespace Database\Seeders;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TicketSeeder extends Seeder
{
    public const TICKETS = 1000;

    private const OPEN_SHARE = 70;

    public function run(): void
    {
        if (User::query()->doesntExist()) {
            $this->command?->warn('Nenhum usuario cadastrado: rode o UserSeeder antes.');

            return;
        }

        $open = (int) round(self::TICKETS * self::OPEN_SHARE / 100);
        $finished = self::TICKETS - $open;

        DB::transaction(function () use ($open, $finished) {
            Ticket::factory()->count($open)->open()->create();

            Ticket::factory()->count($finished)->finished()->create();
        });

        $this->report();
    }

    private function report(): void
    {
        $this->command?->info(sprintf(
            'Chamados: %d total (%d em aberto, %d finalizados).',
            Ticket::count(),
            Ticket::whereIn('status', TicketStatus::openStatuses())->count(),
            Ticket::whereNotIn('status', TicketStatus::openStatuses())->count(),
        ));
    }
}
