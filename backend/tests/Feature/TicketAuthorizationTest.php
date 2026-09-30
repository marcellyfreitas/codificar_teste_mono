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
    // O autor é fixo e nunca é quem está acting: como o dono de um chamado
    // aberto pode editá-lo, deixar o factory sortear o autor tornaria
    // flutuante qualquer teste de negação.
    $autor = User::factory()->regular()->create();

    return Ticket::factory()
        ->createdBy($autor)
        ->forAssignee(User::factory()->gestor()->create())
        ->open()
        ->create($atributos + ['status' => 'open']);
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

it('recusa edicao de chamado que o usuario comum nao abriu', function () {
    $comum = usuarioComPapel(UserRole::USER);

    Sanctum::actingAs($comum);

    $ticket = chamadoDeUmGestor(['title' => 'Titulo original']);

    test()->putJson("/api/v1/tickets/{$ticket->id}", ['title' => 'Hackeado'])
        ->assertForbidden()
        ->assertJsonPath('message', 'Você só pode editar os chamados que abriu.');

    expect($ticket->fresh()->title)->toBe('Titulo original');
});

it('recusa edicao de status por usuario comum', function () {
    Sanctum::actingAs(usuarioComPapel(UserRole::USER));

    $ticket = chamadoDeUmGestor(['status' => 'open']);

    // A validação roda antes da policy, então o status chega barrado como
    // campo proibido — e não como 403 de autorização.
    test()->putJson("/api/v1/tickets/{$ticket->id}", ['status' => 'closed'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('status');

    expect($ticket->fresh()->status)->toBe('open');
});

it('recusa reatribuicao de responsavel por usuario comum', function () {
    Sanctum::actingAs(usuarioComPapel(UserRole::USER));

    $gestor = User::factory()->gestor()->create();
    $ticket = chamadoDeUmGestor();

    test()->putJson("/api/v1/tickets/{$ticket->id}", ['assignee_id' => $gestor->id])->assertForbidden();

    expect($ticket->fresh()->assignee_id)->not->toBe($gestor->id);
});

it('permite ao usuario comum editar o proprio chamado aberto', function () {
    $comum = usuarioComPapel(UserRole::USER);

    $ticket = Ticket::factory()
        ->createdBy($comum)
        ->create(['status' => 'open', 'title' => 'Titulo original']);

    Sanctum::actingAs($comum);

    test()->putJson("/api/v1/tickets/{$ticket->id}", [
        'title' => 'Titulo corrigido pelo dono',
        'description' => 'Detalhe revisado.',
        'priority' => 'high',
    ])->assertOk();

    expect($ticket->fresh())
        ->title->toBe('Titulo corrigido pelo dono')
        ->description->toBe('Detalhe revisado.')
        ->priority->toBe('high');
});

it('recusa edicao do proprio chamado quando nao esta mais aberto', function (string $status) {
    $comum = usuarioComPapel(UserRole::USER);

    $ticket = Ticket::factory()
        ->createdBy($comum)
        ->create(['status' => $status, 'title' => 'Titulo original']);

    Sanctum::actingAs($comum);

    test()->putJson("/api/v1/tickets/{$ticket->id}", ['title' => 'Hackeado'])
        ->assertForbidden()
        ->assertJsonPath('message', 'Você só pode editar o chamado enquanto ele estiver aberto.');

    expect($ticket->fresh()->title)->toBe('Titulo original');
})->with(['in_progress', 'resolved', 'closed']);

it('recusa mudanca de status pelo usuario comum no proprio chamado', function () {
    $comum = usuarioComPapel(UserRole::USER);

    $ticket = Ticket::factory()->createdBy($comum)->create(['status' => 'open']);

    Sanctum::actingAs($comum);

    test()->putJson("/api/v1/tickets/{$ticket->id}", ['status' => 'closed'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('status')
        ->assertJsonPath(
            'errors.status.0',
            'O campo status só pode ser alterado por gestores e administradores.',
        );

    expect($ticket->fresh()->status)->toBe('open');
});

it('permite ao usuario comum escolher o responsavel do proprio chamado aberto', function () {
    $comum = usuarioComPapel(UserRole::USER);
    $gestor = User::factory()->gestor()->create();

    $ticket = Ticket::factory()->createdBy($comum)->create(['status' => 'open']);

    Sanctum::actingAs($comum);

    test()->putJson("/api/v1/tickets/{$ticket->id}", ['assignee_id' => $gestor->id])
        ->assertOk();

    expect($ticket->fresh()->assignee_id)->toBe($gestor->id);
});

it('recita responsavel que nao e gestor, mesmo para o proprio chamado', function () {
    $comum = usuarioComPapel(UserRole::USER);
    $outro = User::factory()->regular()->create();

    $ticket = Ticket::factory()->createdBy($comum)->create(['status' => 'open']);

    Sanctum::actingAs($comum);

    test()->putJson("/api/v1/tickets/{$ticket->id}", ['assignee_id' => $outro->id])
        ->assertStatus(422)
        ->assertJsonValidationErrors('assignee_id');

    expect($ticket->fresh()->assignee_id)->not->toBe($outro->id);
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
    'comum: nao edita chamado alheio' => [UserRole::USER, false, false],
    'gestor: edita e exclui' => [UserRole::GESTOR, true, true],
    'admin: edita e exclui' => [UserRole::ADMIN, true, true],
]);

it('liberia o usuario comum so no proprio chamado aberto', function (string $status, bool $edita) {
    $comum = usuarioComPapel(UserRole::USER);

    $ticket = Ticket::factory()->createdBy($comum)->create(['status' => $status]);

    expect(Gate::forUser($comum)->allows('update', $ticket))->toBe($edita);
})->with([
    'aberto' => ['open', true],
    'em andamento' => ['in_progress', false],
    'resolvido' => ['resolved', false],
    'finalizado' => ['closed', false],
]);

// `TicketFactory::open()` sorteia entre `open` e `in_progress`. Onde o status
// decide o resultado, ele precisa ser fixado — senão o teste passa ou falha
// conforme o sorteio.
it('decide o acesso pelo status fixo do chamado', function (string $status, int $codigo) {
    $comum = usuarioComPapel(UserRole::USER);

    $ticket = Ticket::factory()
        ->createdBy($comum)
        ->create(['status' => $status, 'title' => 'Titulo original']);

    Sanctum::actingAs($comum);

    test()->putJson("/api/v1/tickets/{$ticket->id}", ['title' => 'Outro titulo'])
        ->assertStatus($codigo);

    expect($ticket->fresh()->title)
        ->toBe($codigo === 200 ? 'Outro titulo' : 'Titulo original');
})->with([
    'aberto: edita' => ['open', 200],
    'em andamento: barrado' => ['in_progress', 403],
    'resolvido: barrado' => ['resolved', 403],
    'finalizado: barrado' => ['closed', 403],
]);

it('libera o gestor em qualquer status, mesmo sem ser o dono', function (string $status) {
    $gestor = usuarioComPapel(UserRole::GESTOR);

    $ticket = Ticket::factory()
        ->createdBy(User::factory()->regular()->create())
        ->create(['status' => $status]);

    expect(Gate::forUser($gestor)->allows('update', $ticket))->toBeTrue();
})->with(['open', 'in_progress', 'resolved', 'closed']);

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
