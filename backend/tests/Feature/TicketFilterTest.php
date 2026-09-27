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

it('nao inclui chamado excluido em nenhum filtro', function () {
    $gestor = User::factory()->gestor()->create();
    $autor = User::factory()->regular()->create();

    $ticket = Ticket::factory()->createdBy($autor)->forAssignee($gestor)->create();
    $ticket->delete();

    listaComoGestor();

    test()->getJson('/api/v1/tickets?user_id='.$autor->id)->assertJsonCount(0, 'data');
    test()->getJson('/api/v1/tickets?assignee_id='.$gestor->id)->assertJsonCount(0, 'data');
});
