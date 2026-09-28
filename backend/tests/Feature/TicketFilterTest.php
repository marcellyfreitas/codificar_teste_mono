<?php

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function listaComoGestor(): void
{
    Sanctum::actingAs(User::factory()->gestor()->create());
}

it('filtra por responsavel', function () {
    $gestor = User::factory()->gestor()->create();
    $outro = User::factory()->gestor()->create();

    $meu = Ticket::factory()->forAssignee($gestor)->create();
    Ticket::factory()->forAssignee($outro)->create();

    listaComoGestor();

    test()->getJson('/api/v1/tickets?assignee_id='.$gestor->id)
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $meu->id)
        ->assertJsonPath('data.0.assignee.id', $gestor->id);
});

it('filtra por autor', function () {
    $autor = User::factory()->regular()->create();
    $outro = User::factory()->regular()->create();

    $meu = Ticket::factory()->createdBy($autor)->create();
    Ticket::factory()->createdBy($outro)->create();

    listaComoGestor();

    test()->getJson('/api/v1/tickets?user_id='.$autor->id)
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $meu->id)
        ->assertJsonPath('data.0.user.id', $autor->id);
});

it('separa os dois filtros quando o papel e o oposto do desejado', function () {
    $quemAbre = User::factory()->regular()->create();
    $quemTrata = User::factory()->gestor()->create();

    Ticket::factory()->createdBy($quemAbre)->forAssignee($quemTrata)->create();

    listaComoGestor();

    test()->getJson('/api/v1/tickets?user_id='.$quemAbre->id)
        ->assertOk()
        ->assertJsonCount(1, 'data');

    test()->getJson('/api/v1/tickets?assignee_id='.$quemTrata->id)
        ->assertOk()
        ->assertJsonCount(1, 'data');

    test()->getJson('/api/v1/tickets?user_id='.$quemTrata->id)
        ->assertOk()
        ->assertJsonCount(0, 'data');

    test()->getJson('/api/v1/tickets?assignee_id='.$quemAbre->id)
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

it('combina os dois filtros', function () {
    $a = User::factory()->regular()->create();
    $b = User::factory()->regular()->create();
    $g = User::factory()->gestor()->create();

    $alvo = Ticket::factory()->createdBy($a)->forAssignee($g)->create();
    Ticket::factory()->createdBy($a)->forAssignee(User::factory()->gestor()->create())->create();
    Ticket::factory()->createdBy($b)->forAssignee($g)->create();

    listaComoGestor();

    test()->getJson("/api/v1/tickets?user_id={$a->id}&assignee_id={$g->id}")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $alvo->id);
});

it('ignora filtro de usuario que nao e numerico, em vez de quebrar', function () {
    listaComoGestor();

    Ticket::factory()->count(2)->create();

    test()->getJson('/api/v1/tickets?user_id=abc&assignee_id=xyz')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('mantem os demais filtros intactos ao lado dos dois novos', function (string $campo, string $valor, int $esperados) {
    $g = User::factory()->gestor()->create();

    Ticket::factory()->create(['status' => 'open', 'priority' => 'high', 'title' => 'Impressora quebrada']);
    Ticket::factory()->create(['status' => 'closed', 'priority' => 'low', 'title' => 'Mouse com defeito']);
    Ticket::factory()->create(['status' => 'open', 'priority' => 'low', 'title' => 'Teclado travando']);

    listaComoGestor();

    test()->getJson('/api/v1/tickets?'.$campo.'='.$valor)
        ->assertOk()
        ->assertJsonCount($esperados, 'data');
})->with([
    'status open' => ['status', 'open', 2],
    'status closed' => ['status', 'closed', 1],
    'priority high' => ['priority', 'high', 1],
    'search por titulo' => ['search', 'Impressora', 1],
]);

it('filtra por intervalo de data em created_at', function () {
    // Antes dos chamados: a factory sorteia o autor entre os usuarios que ja
    // existem, e `user_id` e NOT NULL.
    listaComoGestor();

    $antigo = Ticket::factory()->create(['created_at' => now()->subDays(40)]);
    $dentro = Ticket::factory()->create(['created_at' => now()->subDays(10)]);
    $recente = Ticket::factory()->create(['created_at' => now()->subDay()]);

    test()->getJson('/api/v1/tickets?created_from='.now()->subDays(30)->toDateString().'&created_to='.now()->toDateString())
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.id', $recente->id)
        ->assertJsonPath('data.1.id', $dentro->id);

    expect($antigo->id)->not->toBe($dentro->id);
});

it('inclui o dia final inteiro no filtro de data', function () {
    listaComoGestor();

    // Meia-noite do dia final: um `where` por instante, ou um `<` em vez de
    // `<=`, deixaria este chamado de fora. O filtro e por dia, nao por instante.
    $meiaNoite = Ticket::factory()->create(['created_at' => now()->subDays(2)->startOfDay()]);
    $depois = Ticket::factory()->create(['created_at' => now()->subDay()->startOfDay()]);

    $diaFinal = now()->subDays(2)->toDateString();

    test()->getJson('/api/v1/tickets?created_to='.$diaFinal)
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $meiaNoite->id);

    expect($depois->id)->not->toBe($meiaNoite->id);
});

it('aceita as duas pontas do intervalo de forma independente', function () {
    listaComoGestor();

    Ticket::factory()->create(['created_at' => now()->subDays(50)]);
    Ticket::factory()->create(['created_at' => now()->subDays(20)]);
    Ticket::factory()->create(['created_at' => now()->subDays(2)]);

    // So a ponta de baixo: os dois chamados recentes.
    test()->getJson('/api/v1/tickets?created_from='.now()->subDays(30)->toDateString())
        ->assertOk()
        ->assertJsonCount(2, 'data');

    // So a ponta de cima: apenas o chamado antigo. Nenhuma das duas barras
    // pode virar obrigatoria por acidente.
    test()->getJson('/api/v1/tickets?created_to='.now()->subDays(30)->toDateString())
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('ignora data invalida em vez de quebrar a requisicao', function (string $valor) {
    listaComoGestor();

    Ticket::factory()->count(2)->create();

    test()->getJson('/api/v1/tickets?created_from='.$valor.'&created_to='.$valor)
        ->assertOk()
        ->assertJsonCount(2, 'data');
})->with([
    'texto' => ['abc'],
    'data impossivel' => ['2026-13-45'],
    'formato brasileiro' => ['28/09/2026'],
    'data com hora' => ['2026-09-28T10:00:00'],
    'ano com cinco digitos' => ['12026-09-28'],
]);

it('combina o filtro de data com o de responsavel', function () {
    $gestor = User::factory()->gestor()->create();
    $outro = User::factory()->gestor()->create();

    listaComoGestor();

    $alvo = Ticket::factory()->forAssignee($gestor)->create(['created_at' => now()->subDays(3)]);
    Ticket::factory()->forAssignee($gestor)->create(['created_at' => now()->subDays(60)]);
    Ticket::factory()->forAssignee($outro)->create(['created_at' => now()->subDays(3)]);

    test()->getJson(
        '/api/v1/tickets?assignee_id='.$gestor->id
        .'&created_from='.now()->subDays(30)->toDateString()
    )
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $alvo->id);
});

it('nao inclui chamado excluido no filtro de data', function () {
    listaComoGestor();

    $ticket = Ticket::factory()->create(['created_at' => now()->subDay()]);
    $ticket->delete();

    test()->getJson('/api/v1/tickets?created_from='.now()->subDays(7)->toDateString())
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

it('nao inclui chamado excluido em nenhum filtro', function () {
    $gestor = User::factory()->gestor()->create();
    $autor = User::factory()->regular()->create();

    $ticket = Ticket::factory()->createdBy($autor)->forAssignee($gestor)->create();
    $ticket->delete();

    listaComoGestor();

    test()->getJson('/api/v1/tickets?user_id='.$autor->id)->assertJsonCount(0, 'data');
    test()->getJson('/api/v1/tickets?assignee_id='.$gestor->id)->assertJsonCount(0, 'data');
});
