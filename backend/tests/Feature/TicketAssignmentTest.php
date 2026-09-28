<?php

use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function ticketPayload(array $overrides = []): array
{
    return array_merge([
        'title' => 'Chamado de teste',
        'description' => 'Descricao do chamado de teste.',
        'priority' => 'medium',
    ], $overrides);
}

function createTicket(array $overrides = [])
{
    $user = User::factory()->create();

    Sanctum::actingAs($user);

    return [
        test()->postJson('/api/v1/tickets', ticketPayload($overrides)),
        $user,
    ];
}

function gestores(int $count): void
{
    User::factory()->count($count)->gestor()->create();
}

it('atribui automaticamente o responsavel com menos chamados em aberto', function () {
    gestores(3);

    $pool = User::query()->where('role', 'gestor')->orderBy('id')->get();

    $busy = $pool->first();
    Ticket::factory()->count(3)->open()->forAssignee($busy)->create(['status' => 'open']);
    Ticket::factory()->count(2)->finished()->forAssignee($busy)->create();

    [$response] = createTicket();

    $response->assertCreated();

    $expected = User::query()
        ->where('role', 'gestor')
        ->where('id', '!=', $busy->id)
        ->orderBy('id')
        ->value('id');

    $response->assertJsonPath('data.assignee_id', $expected);
});

it('conta a carga pelos chamados ATRIBUIDOS, nunca pelos que o gestor abriu', function () {
    gestores(2);

    [$queAtende, $queSoabri] = User::query()->where('role', 'gestor')->orderBy('id')->get()->all();

    Ticket::factory()->count(3)->open()->forAssignee($queAtende)->createdBy($queSoabri)->create();

    $porAtribuicao = $queAtende->assignedTickets()->count() - $queSoabri->assignedTickets()->count();
    $porAbertura = $queSoabri->tickets()->count() - $queAtende->tickets()->count();

    expect($porAtribuicao)->toBe(3)
        ->and($porAbertura)->toBe(3);

    [$response] = createTicket();

    $response->assertCreated()
        ->assertJsonPath('data.assignee_id', $queSoabri->id);
});

it('nao confere um responsavel para o proprio autor comum', function () {
    $comum = User::factory()->regular()->create();
    $gestor = User::factory()->gestor()->create();

    Sanctum::actingAs($comum);

    $response = test()->postJson('/api/v1/tickets', ticketPayload());

    $response->assertCreated()
        ->assertJsonPath('data.user.id', $comum->id)
        ->assertJsonPath('data.assignee_id', $gestor->id);
});

it('cria o chamado sem responsavel quando auto_assign e false', function () {
    gestores(3);

    [$response, $autor] = createTicket(['auto_assign' => false]);

    $response->assertCreated()
        ->assertJsonPath('data.assignee_id', null)
        ->assertJsonPath('data.assignee', null)
        ->assertJsonPath('data.user.id', $autor->id);

    expect(array_key_exists('assignee_id', $response->json('data')))->toBeTrue();
});

it('mantem a atribuicao automatica quando auto_assign e true', function () {
    gestores(1);

    [$response] = createTicket(['auto_assign' => true]);

    $response->assertCreated();

    expect($response->json('data.assignee_id'))->not->toBeNull();
});

it('mantem o comportamento padrao quando auto_assign nao e enviado', function () {
    gestores(1);

    [$response] = createTicket();

    $response->assertCreated();

    expect($response->json('data.assignee_id'))->not->toBeNull();
});

it('respeita o responsavel informado mesmo com auto_assign false', function () {
    $target = User::factory()->gestor()->create();
    gestores(1);

    [$response] = createTicket(['auto_assign' => false, 'assignee_id' => $target->id]);

    $response->assertCreated()->assertJsonPath('data.assignee_id', $target->id);
});

it('atribui ao gestor de menor id quando todos estao igualmente ociosos', function () {
    gestores(3);

    [$response] = createTicket();

    $response->assertCreated()
        ->assertJsonPath('data.assignee_id', User::query()->min('id'));
});

it('ignora chamados finalizados ao calcular a carga', function () {
    $idle = User::factory()->gestor()->create();
    $busy = User::factory()->gestor()->create();

    Ticket::factory()->count(5)->finished()->forAssignee($busy)->create();

    [$response] = createTicket();

    $response->assertCreated()->assertJsonPath('data.assignee_id', $idle->id);
});

it('conta chamados in_progress como carga de trabalho', function () {
    $idle = User::factory()->gestor()->create();
    $busy = User::factory()->gestor()->create();

    Ticket::factory()->count(3)->forAssignee($busy)->create(['status' => 'in_progress']);

    [$response] = createTicket();

    $response->assertCreated()->assertJsonPath('data.assignee_id', $idle->id);
});

it('cria o chamado sem responsavel quando nao existe nenhum gestor elegivel', function () {
    $autor = User::factory()->regular()->create();

    expect(User::where('role', UserRole::GESTOR->value)->count())->toBe(0);

    Sanctum::actingAs($autor);

    $response = test()->postJson('/api/v1/tickets', ticketPayload());

    $response->assertCreated()
        ->assertJsonPath('data.assignee_id', null)
        ->assertJsonPath('data.assignee', null)
        ->assertJsonPath('data.user.id', $autor->id);
});

it('desresponsabiliza um chamado existente via assignee_id null', function () {
    $user = User::factory()->gestor()->create();
    $ticket = Ticket::factory()->forAssignee($user)->create();

    Sanctum::actingAs($user);

    $this->putJson("/api/v1/tickets/{$ticket->id}", ['assignee_id' => null])
        ->assertOk()
        ->assertJsonPath('data.assignee_id', null);

    expect($ticket->fresh()->assignee_id)->toBeNull();
});

it('nao altera o responsavel quando o update nao envia assignee_id', function () {
    $user = User::factory()->gestor()->create();
    $ticket = Ticket::factory()->forAssignee($user)->create();

    Sanctum::actingAs($user);

    $this->putJson("/api/v1/tickets/{$ticket->id}", ['priority' => 'high'])
        ->assertOk()
        ->assertJsonPath('data.assignee_id', $user->id);
});

it('rejeita auto_assign com valor nao booleano', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user);

    $this->postJson('/api/v1/tickets', ticketPayload(['auto_assign' => 'talvez']))
        ->assertStatus(422)
        ->assertJsonValidationErrors('auto_assign');
});

it('nao persiste auto_assign como coluna do chamado', function () {
    gestores(1);

    [$response] = createTicket(['auto_assign' => false]);

    $response->assertCreated();

    expect(array_keys($response->json('data')))->not->toContain('auto_assign');
});

it('mantem os enums de status na carga de trabalho', function () {
    $busy = User::factory()->gestor()->create();

    expect(TicketStatus::openStatuses())->toBe(['open', 'in_progress']);

    Ticket::factory()->count(4)->forAssignee($busy)->create([
        'status' => TicketStatus::CLOSED->value,
    ]);

    [$response] = createTicket();

    $response->assertCreated()->assertJsonPath('data.assignee_id', $busy->id);
});

it('atribui o chamado a um gestor quando so existem usuarios comuns', function () {
    User::factory()->count(2)->regular()->create();
    User::factory()->admin()->create();

    [$response] = createTicket();

    $response->assertCreated()->assertJsonPath('data.assignee_id', null);
});

it('ignora admins e usuarios comuns ao escolher o menos carregado', function () {
    $gestor = User::factory()->gestor()->create();

    Ticket::factory()->count(9)->forAssignee(User::factory()->admin()->create())->create(['status' => 'open']);
    Ticket::factory()->count(9)->forAssignee(User::factory()->regular()->create())->create(['status' => 'open']);

    [$response] = createTicket();

    $response->assertCreated()->assertJsonPath('data.assignee_id', $gestor->id);
});

it('recusa atribuir o chamado a um usuario comum', function () {
    $comum = User::factory()->regular()->create();

    [$response] = createTicket(['assignee_id' => $comum->id]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors('assignee_id');
});

it('recusa atribuir o chamado a um administrador', function () {
    $admin = User::factory()->admin()->create();

    [$response] = createTicket(['assignee_id' => $admin->id]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors('assignee_id');
});

it('recusa reatribuir um chamado para um nao gestor', function () {
    $gestor = User::factory()->gestor()->create();
    $comum = User::factory()->regular()->create();
    $ticket = Ticket::factory()->forAssignee($gestor)->create();

    Sanctum::actingAs($gestor);

    $this->putJson("/api/v1/tickets/{$ticket->id}", ['assignee_id' => $comum->id])
        ->assertStatus(422)
        ->assertJsonValidationErrors('assignee_id');

    expect($ticket->fresh()->assignee_id)->toBe($gestor->id);
});

it('aceita reatribuir um chamado para outro gestor', function () {
    $origem = User::factory()->gestor()->create();
    $destino = User::factory()->gestor()->create();
    $ticket = Ticket::factory()->forAssignee($origem)->create();

    Sanctum::actingAs($origem);

    $this->putJson("/api/v1/tickets/{$ticket->id}", ['assignee_id' => $destino->id])
        ->assertOk()
        ->assertJsonPath('data.assignee_id', $destino->id);
});
