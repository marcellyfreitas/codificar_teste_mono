<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Prefixo do protocolo. Formato final: CH-AAAA-NNNNN. */
    private const PREFIX = 'CH';

    /** Casas reservadas para a sequencia dentro do ano. */
    private const PADDING = 5;

    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->string('protocol')->nullable();
        });

        $this->backfill();

        Schema::table('tickets', function (Blueprint $table) {
            $table->unique('protocol');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropUnique(['protocol']);
            $table->dropColumn('protocol');
        });
    }

    /**
     * Os chamados ja existentes nao passaram pelo gerador, entao recebem aqui
     * um protocolo proprio. A ordem e por data de abertura para que a
     * sequencia de cada ano acompanhe a ordem em que os chamados surgiram.
     */
    private function backfill(): void
    {
        $pending = DB::table('tickets')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get(['id', 'created_at']);

        $sequences = [];

        foreach ($pending as $ticket) {
            $year = date('Y', strtotime((string) $ticket->created_at));

            $sequences[$year] = ($sequences[$year] ?? 0) + 1;

            DB::table('tickets')
                ->where('id', $ticket->id)
                ->update(['protocol' => $this->format($year, $sequences[$year])]);
        }
    }

    private function format(string $year, int $sequence): string
    {
        return sprintf('%s-%s-%0*d', self::PREFIX, $year, self::PADDING, $sequence);
    }
};
