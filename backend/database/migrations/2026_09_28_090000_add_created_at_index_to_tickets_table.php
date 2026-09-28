<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // O filtro de data da listagem vira varredura completa da tabela sem
        // este indice. O quadro Kanban e o caso que motiva o filtro, entao a
        // janela de datas precisa ser barata de verdade.
        Schema::table('tickets', function (Blueprint $table) {
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
        });
    }
};
