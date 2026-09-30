<?php

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function payloadDeProtocolo(array $overrides = []): array
{
    return array_merge([
        'title' => 'Chamado de teste',
        'description' => 'Descricao do chamado de teste.',
        'priority' => 'medium',
    ], $overrides);
}

it('gera o protocolo no formato CH-AAAA-NNNNN ao abrir chamado pela api', function () {
    $autor = User::factory()->regular()->create();
    User::factory()->gestor()->create();

    Sanctum::actingAs($autor);

    test()->postJson('/api/v1/tickets', payloadDeProtocolo())
        ->assertCreated()
        ->assertJsonPath('data.protocol', 'CH-'.now()->format('Y').'-00001');
});

it('gera o protocolo tambem quando o chamado nasce pela factory, fora do service', function () {
    // A factory sorteia o autor entre os usuarios que ja existem, entao ela
    // precisa de pelo menos um cadastro antes.
    User::factory()->create();
    User::factory()->gestor()->create();

    Ticket::factory()->count(2)->create();

    foreach (Ticket::orderBy('id')->get() as $chamado) {
        expect($chamado->protocol)->toMatch('/^CH-\d{4}-\d{5}$/');
    }
});

it('incrementa a sequencia do protocolo a cada chamado, sem repetir', function () {
    User::factory()->create();
    User::factory()->gestor()->create();

    $criados = [];

    foreach (range(1, 5) as $ignored) {
        $criados[] = Ticket::factory()->create()->protocol;
    }

    expect($criados)->toHaveCount(5)
        ->and(array_unique($criados))->toHaveCount(5)
        ->and($criados[0])->toBe('CH-'.now()->format('Y').'-00001')
        ->and($criados[4])->toBe('CH-'.now()->format('Y').'-00005');
});

it('recusa dois chamados com o mesmo protocolo', function () {
    User::factory()->create();
    User::factory()->gestor()->create();

    Ticket::factory()->create(['protocol' => 'CH-2026-00001']);

    expect(fn () => Ticket::factory()->create(['protocol' => 'CH-2026-00001']))
        ->toThrow(QueryException::class);
});

it('recusa o mesmo protocolo em anos diferentes', function () {
    // A sequencia reinicia a cada ano, mas o ano faz parte do protocolo,
    // entao CH-2025-00001 e CH-2026-00001 sao chamados distintos.
    User::factory()->create();
    User::factory()->gestor()->create();

    Ticket::factory()->create(['protocol' => 'CH-2025-00001']);
    Ticket::factory()->create(['protocol' => 'CH-2026-00001']);

    expect(Ticket::count())->toBe(2);
});

it('nao deixa o protocolo ser alterado pela edicao do chamado', function () {
    $gestor = User::factory()->gestor()->create();
    $chamado = Ticket::factory()->forAssignee($gestor)->create([
        'title' => 'Titulo original',
        'protocol' => 'CH-2026-00777',
    ]);

    Sanctum::actingAs($gestor);

    test()->putJson('/api/v1/tickets/'.$chamado->id, [
        'title' => 'Titulo editado',
        'protocol' => 'CH-2026-09999',
    ])->assertOk();

    expect($chamado->fresh()->title)->toBe('Titulo editado')
        ->and($chamado->fresh()->protocol)->toBe('CH-2026-00777');
});

it('nao reatribui a sequencia depois de remover um chamado', function () {
    User::factory()->create();
    User::factory()->gestor()->create();

    Ticket::factory()->create(['protocol' => 'CH-2026-00300']);
    $removido = Ticket::factory()->create(['protocol' => 'CH-2026-00301']);

    $removido->delete();

    // Soft delete mantem a linha na base, entao o proximo chamado continua
    // depois dela em vez de reusar o numero.
    expect(Ticket::factory()->create()->protocol)->toBe('CH-'.now()->format('Y').'-00302');
});
