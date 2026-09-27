<?php

use App\Enums\UserRole;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function usuarioComPapel(UserRole $papel): User
{
    return User::factory()->create(['role' => $papel])->fresh();
}

function chamadoDeUmGestor(array $atributos = []): Ticket
{
    return Ticket::factory()
        ->forAssignee(User::factory()->gestor()->create())
        ->open()
        ->create($atributos);
}

it('recusa exclusao por usuario comum', function () {
    $comum = usuarioComPapel(UserRole::USER);
    $ticket = chamadoDeUmGestor(['title' => 'Nao pode sumir']);

    Sanctum::actingAs($comum);

    test()->deleteJson("/api/v1/tickets/{$ticket->id}")
        ->assertForbidden()
        ->assertJsonPath('message', 'Apenas gestores e administradores podem excluir chamados.');

    expect(Ticket::find($ticket->id))->not->toBeNull();
});

it('permite exclusao por gestor', function () {
    Sanctum::actingAs(usuarioComPapel(UserRole::GESTOR));

    $ticket = chamadoDeUmGestor();

    test()->deleteJson("/api/v1/tickets/{$ticket->id}")->assertNoContent();

    expect(Ticket::find($ticket->id))->toBeNull();
});

it('permite exclusao por administrador', function () {
    Sanctum::actingAs(usuarioComPapel(UserRole::ADMIN));

    $ticket = chamadoDeUmGestor();

    test()->deleteJson("/api/v1/tickets/{$ticket->id}")->assertNoContent();

    expect(Ticket::find($ticket->id))->toBeNull();
});

it('recusa exclusao sem token', function () {
    $ticket = chamadoDeUmGestor();

    test()->deleteJson("/api/v1/tickets/{$ticket->id}")->assertUnauthorized();

    expect(Ticket::find($ticket->id))->not->toBeNull();
});

it('recusa exclusao de chamado inexistente para usuario comum com 403', function () {
    Sanctum::actingAs(usuarioComPapel(UserRole::USER));

    test()->deleteJson('/api/v1/tickets/999999')->assertNotFound();
});

it('recusa edicao por usuario comum', function () {
    Sanctum::actingAs(usuarioComPapel(UserRole::USER));

    $ticket = chamadoDeUmGestor(['title' => 'Titulo original']);

    test()->putJson("/api/v1/tickets/{$ticket->id}", ['title' => 'Hackeado'])
        ->assertForbidden()
        ->assertJsonPath('message', 'Apenas gestores e administradores podem editar chamados.');

    expect($ticket->fresh()->title)->toBe('Titulo original');
});

it('recusa edicao de status por usuario comum', function () {
    Sanctum::actingAs(usuarioComPapel(UserRole::USER));

    $ticket = chamadoDeUmGestor(['status' => 'open']);

    test()->putJson("/api/v1/tickets/{$ticket->id}", ['status' => 'closed'])->assertForbidden();

    expect($ticket->fresh()->status)->toBe('open');
});

it('recusa reatribuicao de responsavel por usuario comum', function () {
    Sanctum::actingAs(usuarioComPapel(UserRole::USER));

    $gestor = User::factory()->gestor()->create();
    $ticket = chamadoDeUmGestor();

    test()->putJson("/api/v1/tickets/{$ticket->id}", ['assignee_id' => $gestor->id])->assertForbidden();

    expect($ticket->fresh()->assignee_id)->not->toBe($gestor->id);
});

it('permite edicao por gestor', function () {
    Sanctum::actingAs(usuarioComPapel(UserRole::GESTOR));

    $ticket = chamadoDeUmGestor(['title' => 'Titulo original']);

    test()->putJson("/api/v1/tickets/{$ticket->id}", ['title' => 'Corrigido pelo gestor'])
        ->assertOk();

    expect($ticket->fresh()->title)->toBe('Corrigido pelo gestor');
});

it('permite edicao por administrador', function () {
    Sanctum::actingAs(usuarioComPapel(UserRole::ADMIN));

    $ticket = chamadoDeUmGestor(['title' => 'Titulo original']);

    test()->putJson("/api/v1/tickets/{$ticket->id}", ['title' => 'Corrigido pelo admin'])
        ->assertOk();

    expect($ticket->fresh()->title)->toBe('Corrigido pelo admin');
});

it('recusa edicao sem token', function () {
    $ticket = chamadoDeUmGestor(['title' => 'Titulo original']);

    test()->putJson("/api/v1/tickets/{$ticket->id}", ['title' => 'Anonimo'])->assertUnauthorized();

    expect($ticket->fresh()->title)->toBe('Titulo original');
});

it('permite criar chamado para qualquer papel', function (UserRole $papel) {
    Sanctum::actingAs(usuarioComPapel($papel));

    test()->postJson('/api/v1/tickets', [
        'title' => 'Chamado aberto por '.($papel->value),
        'description' => 'Descricao do chamado.',
        'priority' => 'medium',
    ])->assertCreated();
})->with([
    'usuario comum' => [UserRole::USER],
    'gestor' => [UserRole::GESTOR],
    'admin' => [UserRole::ADMIN],
]);

it('libera a leitura do que o proprio usuario abriu', function () {
    $comum = usuarioComPapel(UserRole::USER);
    $ticket = Ticket::factory()->createdBy($comum)->create();

    Sanctum::actingAs($comum);

    test()->getJson('/api/v1/tickets')->assertOk();
    test()->getJson("/api/v1/tickets/{$ticket->id}")->assertOk();
});

it('aplica a matriz de permissoes da policy', function (UserRole $papel, bool $edita, bool $exclui) {
    $user = usuarioComPapel($papel);
    $ticket = chamadoDeUmGestor();

    $gate = Gate::forUser($user);

    expect($gate->allows('update', $ticket))->toBe($edita)
        ->and($gate->allows('delete', $ticket))->toBe($exclui);
})->with([
    'comum: nao edita nem exclui' => [UserRole::USER, false, false],
    'gestor: edita e exclui' => [UserRole::GESTOR, true, true],
    'admin: edita e exclui' => [UserRole::ADMIN, true, true],
]);

it('nega em silencio se o usuario for passado como primeiro argumento', function () {
    $gestor = usuarioComPapel(UserRole::GESTOR);
    $ticket = chamadoDeUmGestor();

    expect(Gate::allows('update', [$gestor, $ticket]))->toBeFalse()
        ->and(Gate::allows('delete', [$gestor, $ticket]))->toBeFalse()
        ->and(Gate::forUser($gestor)->allows('update', $ticket))->toBeTrue();
});

it('nega por padrao as abilities que foram removidas da policy', function () {
    $gestor = usuarioComPapel(UserRole::GESTOR);
    $ticket = chamadoDeUmGestor();
    $gate = Gate::forUser($gestor);

    // `viewAny` continua fora da policy de propósito: a listagem filtra no banco
    // (ver TicketVisibilityTest), e um ability de classe não teria chamado em mãos.
    expect($gate->allows('create', [Ticket::class]))->toBeFalse()
        ->and($gate->allows('viewAny', [Ticket::class]))->toBeFalse()
        ->and($gate->allows('update', $ticket))->toBeTrue();
});

it('nega em vez de estourar quando o papel e nulo', function () {
    $user = new User(['name' => 'Legado', 'email' => 'legado@teste.com']);
    $ticket = chamadoDeUmGestor();
    $gate = Gate::forUser($user);

    // Papel nulo cai no caso mais restrito: enxerga só o que abriu, e não é dono
    // deste chamado, então a leitura é negada.
    expect($user->isManager())->toBeFalse()
        ->and($gate->allows('view', $ticket))->toBeFalse()
        ->and($gate->allows('update', $ticket))->toBeFalse()
        ->and($gate->allows('delete', $ticket))->toBeFalse();
});

it('nao deixa o usuario comum se promover por parametro', function () {
    Sanctum::actingAs(usuarioComPapel(UserRole::USER));

    $ticket = chamadoDeUmGestor();

    test()->putJson("/api/v1/tickets/{$ticket->id}", ['title' => 'x', 'role' => 'admin'])
        ->assertForbidden();

    expect($ticket->fresh()->user->role)->not->toBe(UserRole::ADMIN);
});
