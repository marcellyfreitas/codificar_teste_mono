<?php

use App\Enums\UserRole;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function payloadDeAutor(array $overrides = []): array
{
    return array_merge([
        'title' => 'Chamado de teste',
        'description' => 'Descricao do chamado de teste.',
        'priority' => 'medium',
    ], $overrides);
}

it('registra o autor a partir do token de autenticacao', function () {
    $autor = User::factory()->regular()->create();
    $gestor = User::factory()->gestor()->create();

    Sanctum::actingAs($autor);

    test()->postJson('/api/v1/tickets', payloadDeAutor())
        ->assertCreated()
        ->assertJsonPath('data.user.id', $autor->id)
        ->assertJsonPath('data.user.email', $autor->email)
        ->assertJsonPath('data.assignee.id', $gestor->id);
});

it('descarta o autor enviado no payload', function () {
    $autor = User::factory()->regular()->create();
    $terceiro = User::factory()->regular()->create();
    $gestor = User::factory()->gestor()->create();

    Sanctum::actingAs($autor);

    $response = test()->postJson('/api/v1/tickets', payloadDeAutor([
        'user_id' => $terceiro->id,
    ]));

    $response->assertCreated()
        ->assertJsonPath('data.user.id', $autor->id);

    expect(Ticket::query()->whereKey($response->json('data.id'))->value('user_id'))
        ->toBe($autor->id);
});

it('nao valida o autor enviado no payload', function () {
    $autor = User::factory()->regular()->create();
    $gestor = User::factory()->gestor()->create();

    Sanctum::actingAs($autor);

    test()->postJson('/api/v1/tickets', payloadDeAutor(['user_id' => 999_999]))
        ->assertCreated()
        ->assertJsonPath('data.user.id', $autor->id);
});

it('mantem o autor imutavel na edicao', function () {
    $autor = User::factory()->regular()->create();
    $gestor = User::factory()->gestor()->create();

    $ticket = Ticket::factory()->createdBy($autor)->forAssignee($gestor)->create();

    Sanctum::actingAs($gestor);

    test()->putJson("/api/v1/tickets/{$ticket->id}", [
        'title' => 'Titulo corrigido',
        'user_id' => $gestor->id,
    ])->assertOk();

    $fresh = $ticket->fresh();

    expect($fresh->title)->toBe('Titulo corrigido')
        ->and($fresh->user_id)->toBe($autor->id);
});

it('separa as relacoes de autor e responsavel no model', function () {
    $autor = User::factory()->regular()->create();
    $gestor = User::factory()->gestor()->create();

    $ticket = Ticket::factory()->createdBy($autor)->forAssignee($gestor)->create();

    expect($ticket->user->is($autor))->toBeTrue()
        ->and($ticket->assignee->is($gestor))->toBeTrue();

    expect($autor->tickets()->pluck('id')->all())->toBe([$ticket->id])
        ->and($autor->assignedTickets()->count())->toBe(0)
        ->and($gestor->assignedTickets()->pluck('id')->all())->toBe([$ticket->id])
        ->and($gestor->tickets()->count())->toBe(0);
});

it('admite o mesmo usuario como autor e responsavel', function () {
    $gestor = User::factory()->gestor()->create();

    Sanctum::actingAs($gestor);

    test()->postJson('/api/v1/tickets', payloadDeAutor())
        ->assertCreated()
        ->assertJsonPath('data.user.id', $gestor->id)
        ->assertJsonPath('data.assignee_id', $gestor->id);
});

it('mantem o autor quando o responsavel e esvaziado', function () {
    $autor = User::factory()->regular()->create();
    $gestor = User::factory()->gestor()->create();

    $ticket = Ticket::factory()->createdBy($autor)->forAssignee($gestor)->create();

    Sanctum::actingAs($gestor);

    test()->putJson("/api/v1/tickets/{$ticket->id}", ['assignee_id' => null])
        ->assertOk()
        ->assertJsonPath('data.user.id', $autor->id)
        ->assertJsonPath('data.assignee_id', null)
        ->assertJsonPath('data.assignee', null);
});

it('cobra o autor na coluna, sem permitir o responsavel nulo', function () {
    $autor = User::factory()->regular()->create();
    $gestor = User::factory()->gestor()->create();

    $ticket = Ticket::factory()->createdBy($autor)->unassigned()->create();

    expect($ticket->fresh()->user_id)->toBe($autor->id)
        ->and($ticket->fresh()->assignee_id)->toBeNull();
});

it('impede de apagar o autor de um chamado', function () {
    $autor = User::factory()->regular()->create();

    Ticket::factory()->createdBy($autor)->create();

    expect(fn () => $autor->delete())->toThrow(QueryException::class);
});

it('devolve o responsavel para a fila quando ele e apagado', function () {
    $autor = User::factory()->regular()->create();
    $gestor = User::factory()->gestor()->create();

    $ticket = Ticket::factory()->createdBy($autor)->forAssignee($gestor)->create();

    $gestor->delete();

    $fresh = $ticket->fresh();

    expect($fresh)->not->toBeNull()
        ->and($fresh->assignee_id)->toBeNull()
        ->and($fresh->user_id)->toBe($autor->id);
});

it('bloqueia a exclusao de quem e autor, mesmo sendo responsavel de outros chamados', function () {
    $gestor = User::factory()->gestor()->create();

    $colega = User::factory()->regular()->create();
    Ticket::factory()->createdBy($gestor)->forAssignee($gestor)->create();
    Ticket::factory()->count(2)->createdBy($colega)->forAssignee($gestor)->create();

    $carregado = $gestor->assignedTickets()->count();
    expect($carregado)->toBe(3);

    expect(fn () => $gestor->delete())->toThrow(QueryException::class);

    expect($gestor->fresh())->not->toBeNull()
        ->and(Ticket::where('assignee_id', $gestor->id)->count())->toBe(3);
});

it('impede de atribuir o chamado a quem nao e gestor, mesmo sendo o autor', function () {
    $comum = User::factory()->regular()->create();
    $outroComum = User::factory()->regular()->create();

    Sanctum::actingAs($comum);

    test()->postJson('/api/v1/tickets', payloadDeAutor([
        'assignee_id' => $outroComum->id,
    ]))
        ->assertStatus(422)
        ->assertJsonValidationErrors('assignee_id');

    expect(Ticket::withTrashed()->count())->toBe(0);
});

it('sorteia sempre um gestor como responsavel, qualquer que seja o papel do autor', function (UserRole $papel) {
    $autor = User::factory()->create(['role' => $papel->value]);
    $gestor = User::factory()->gestor()->create();

    Sanctum::actingAs($autor);

    $response = test()->postJson('/api/v1/tickets', payloadDeAutor())
        ->assertCreated()
        ->assertJsonPath('data.user.id', $autor->id);

    expect(User::find($response->json('data.assignee_id'))->role)
        ->toBe(UserRole::GESTOR);
})->with([UserRole::USER, UserRole::GESTOR, UserRole::ADMIN]);
