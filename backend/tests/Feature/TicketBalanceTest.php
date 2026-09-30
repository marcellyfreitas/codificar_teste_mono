<?php

use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function novoGestor(): User
{
    return User::factory()->gestor()->create();
}

function poolDeGestores(int $quantidade, int $carga = 0): array
{
    $pool = [];

    for ($i = 0; $i < $quantidade; $i++) {
        $gestor = novoGestor();
        $pool[$gestor->id] = $carga;
        adicionarCarga($gestor, $carga);
    }

    return $pool;
}

function adicionarCarga(User $gestor, int $carga, int $finalizados = 0): void
{
    for ($i = 0; $i < $carga; $i++) {
        Ticket::factory()->forAssignee($gestor)->create(['status' => 'open']);
    }

    for ($i = 0; $i < $finalizados; $i++) {
        Ticket::factory()->forAssignee($gestor)->create(['status' => 'closed']);
    }
}

function enfileirar(int $quantidade, string $status = 'open'): array
{
    $ids = [];

    for ($i = 0; $i < $quantidade; $i++) {
        $ids[] = Ticket::factory()->unassigned()->create([
            'title' => "Fila {$i}",
            'status' => $status,
        ])->id;
    }

    return $ids;
}

function cargaDe(int $userId): int
{
    return Ticket::query()
        ->where('assignee_id', $userId)
        ->whereIn('status', TicketStatus::openStatuses())
        ->count();
}

function actedAsAdmin(): User
{
    $admin = User::factory()->admin()->create();

    Sanctum::actingAs($admin);

    return $admin;
}

function balancear(): TestResponse
{
    return test()->postJson('/api/v1/tickets/balance');
}

function removerResponsaveis(): TestResponse
{
    return test()->postJson('/api/v1/tickets/unassign-open');
}

it('distribui os chamados sem responsavel entre os gestores', function () {
    poolDeGestores(3);
    enfileirar(6);

    actedAsAdmin();

    balancear()->assertOk()
        ->assertJsonPath('data.distributed', 6);

    expect(Ticket::query()->whereNull('assignee_id')->whereIn('status', TicketStatus::openStatuses())->count())
        ->toBe(0);
});

it('escolhe o gestor com menos chamados em aberto', function () {
    $pool = poolDeGestores(3);
    [$a, $b, $c] = array_keys($pool);

    adicionarCarga(User::find($a), 9);
    adicionarCarga(User::find($b), 4);
    adicionarCarga(User::find($c), 0);

    enfileirar(1);

    actedAsAdmin();
    balancear()->assertOk();

    expect(cargaDe($c))->toBe(1)
        ->and(cargaDe($b))->toBe(4)
        ->and(cargaDe($a))->toBe(9);
});

it('conta in_progress como carga de trabalho', function () {
    $pool = poolDeGestores(2);
    [$ocupado, $ocioso] = array_keys($pool);

    for ($i = 0; $i < 5; $i++) {
        Ticket::factory()->forAssignee(User::find($ocupado))->create(['status' => 'in_progress']);
    }

    enfileirar(1);

    actedAsAdmin();
    balancear()->assertOk();

    expect(cargaDe($ocioso))->toBe(1)
        ->and(cargaDe($ocupado))->toBe(5);
});

it('ignora chamados finalizados ao calcular a carga', function () {
    $pool = poolDeGestores(2);
    [$comFinalizados, $semFinalizados] = array_keys($pool);

    adicionarCarga(User::find($comFinalizados), 0, 20);
    adicionarCarga(User::find($semFinalizados), 0, 3);

    enfileirar(1);

    actedAsAdmin();
    balancear()->assertOk();

    expect(cargaDe($comFinalizados))->toBe(1)
        ->and(cargaDe($semFinalizados))->toBe(0);
});

it('equilibra a carga quando a fila e suficiente', function () {
    $pool = poolDeGestores(3);
    [$a, $b, $c] = array_keys($pool);

    adicionarCarga(User::find($a), 5);
    adicionarCarga(User::find($b), 4);
    adicionarCarga(User::find($c), 3);

    enfileirar(6);

    actedAsAdmin();

    balancear()->assertOk()
        ->assertJsonPath('data.distributed', 6)
        ->assertJsonPath('data.difference', 0);

    expect(cargaDe($a))->toBe(6)
        ->and(cargaDe($b))->toBe(6)
        ->and(cargaDe($c))->toBe(6);
});

it('nao remove chamado de responsavel que ja tem', function () {
    $pool = poolDeGestores(2);
    [$a, $b] = array_keys($pool);

    adicionarCarga(User::find($a), 8);
    adicionarCarga(User::find($b), 2);

    enfileirar(2);

    actedAsAdmin();
    balancear()->assertOk();

    expect(cargaDe($a))->toBeGreaterThanOrEqual(8)
        ->and(cargaDe($b))->toBeGreaterThanOrEqual(2);
});

it('mantem a carga de quem ja esta acima do equilibrio', function () {
    $pool = poolDeGestores(2);
    [$acima, $abaixo] = array_keys($pool);

    adicionarCarga(User::find($acima), 20);
    adicionarCarga(User::find($abaixo), 0);

    enfileirar(2);

    actedAsAdmin();

    balancear()->assertOk()
        ->assertJsonPath('data.load_by_gestor.'.$acima, 20)
        ->assertJsonPath('data.load_by_gestor.'.$abaixo, 2)
        ->assertJsonPath('data.difference', 18);
});

it('ignora admins e usuarios comuns no pool', function () {
    $gestor = novoGestor();

    $admin = User::factory()->admin()->create();
    $comum = User::factory()->regular()->create();
    adicionarCarga($admin, 0);
    adicionarCarga($comum, 0);

    enfileirar(1);

    actedAsAdmin();
    balancear()->assertOk();

    expect(cargaDe($gestor->id))->toBe(1)
        ->and(cargaDe($admin->id))->toBe(0)
        ->and(cargaDe($comum->id))->toBe(0);
});

it('nunca atribui um chamado da fila a um nao gestor', function () {
    poolDeGestores(2);

    adicionarCarga(User::factory()->admin()->create(), 3);
    adicionarCarga(User::factory()->regular()->create(), 3);

    $fila = enfileirar(4);

    actedAsAdmin();
    balancear()->assertOk()->assertJsonPath('data.distributed', 4);

    $naoGestores = User::query()
        ->where('role', '!=', UserRole::GESTOR->value)
        ->pluck('id');

    expect(
        Ticket::query()
            ->whereIn('id', $fila)
            ->whereIn('assignee_id', $naoGestores)
            ->count()
    )->toBe(0);

    expect(
        Ticket::query()->whereIn('id', $fila)->whereNotNull('assignee_id')->count()
    )->toBe(4);
});

it('deixa intacto chamado sem responsavel ja finalizado', function () {
    poolDeGestores(2);
    $ids = enfileirar(2, 'closed');
    enfileirar(1, 'open');

    actedAsAdmin();

    balancear()->assertOk()
        ->assertJsonPath('data.distributed', 1)
        ->assertJsonPath('data.kept_open_untouched', 2);

    foreach ($ids as $id) {
        expect(Ticket::find($id)->assignee_id)->toBeNull();
    }
});

it('nao faz nada quando nao existem gestores cadastrados', function () {
    User::factory()->count(3)->regular()->create();
    enfileirar(5);

    actedAsAdmin();

    balancear()->assertOk()
        ->assertJsonPath('data.distributed', 0)
        ->assertJsonPath('data.gestores', 0);

    expect(Ticket::query()->whereNull('assignee_id')->count())->toBe(5);
});

it('nao faz nada quando a fila esta vazia', function () {
    poolDeGestores(3);

    actedAsAdmin();

    balancear()->assertOk()
        ->assertJsonPath('data.distributed', 0)
        ->assertJsonPath('data.kept_open_untouched', 0);
});

it('nao faz nada na segunda execucao', function () {
    poolDeGestores(2);
    enfileirar(3);

    actedAsAdmin();

    balancear()->assertOk()->assertJsonPath('data.distributed', 3);

    balancear()->assertOk()->assertJsonPath('data.distributed', 0);
});

it('desempata pelo menor id de gestor', function () {
    $pool = poolDeGestores(3);
    [$menor] = array_keys($pool);

    enfileirar(3);

    actedAsAdmin();
    balancear()->assertOk();

    foreach (array_keys($pool) as $id) {
        expect(cargaDe($id))->toBe(1);
    }

    expect(cargaDe($menor))->toBe(1);
});

it('devolve a carga por gestor no relatorio', function () {
    $pool = poolDeGestores(2);
    [$a, $b] = array_keys($pool);

    adicionarCarga(User::find($a), 2);
    adicionarCarga(User::find($b), 1);

    enfileirar(2);

    actedAsAdmin();

    balancear()->assertOk()
        ->assertJsonStructure([
            'message',
            'data' => ['distributed', 'kept_open_untouched', 'gestores', 'min_open', 'max_open', 'difference', 'load_by_gestor'],
        ])
        ->assertJsonPath('data.load_by_gestor.'.$a, 3)
        ->assertJsonPath('data.load_by_gestor.'.$b, 2)
        ->assertJsonPath('data.difference', 1);
});

it('exige token para balancear', function () {
    poolDeGestores(1);
    enfileirar(1);

    balancear()->assertUnauthorized();
});

it('recusa usuario comum no balanceamento', function () {
    poolDeGestores(1);
    enfileirar(1);

    Sanctum::actingAs(User::factory()->regular()->create());

    balancear()->assertForbidden();

    expect(Ticket::query()->whereNull('assignee_id')->count())->toBe(1);
});

it('recusa gestor no balanceamento', function () {
    poolDeGestores(1);
    enfileirar(1);

    Sanctum::actingAs(novoGestor());

    balancear()->assertForbidden();
});

it('remove o responsavel de todos os chamados abertos', function () {
    $pool = poolDeGestores(2);
    [$a, $b] = array_keys($pool);

    adicionarCarga(User::find($a), 6);
    adicionarCarga(User::find($b), 4);

    actedAsAdmin();

    removerResponsaveis()->assertOk()
        ->assertJsonPath('data.affected', 10);

    expect(cargaDe($a))->toBe(0)
        ->and(cargaDe($b))->toBe(0);
});

it('remove tambem os in_progress', function () {
    $gestor = novoGestor();

    Ticket::factory()->forAssignee($gestor)->create(['status' => 'in_progress']);
    Ticket::factory()->forAssignee($gestor)->create(['status' => 'open']);

    actedAsAdmin();

    removerResponsaveis()->assertOk()->assertJsonPath('data.affected', 2);

    expect(Ticket::query()->whereNotNull('assignee_id')->count())->toBe(0);
});

it('mantem o responsavel de resolvidos e finalizados', function () {
    $gestor = novoGestor();

    $resolvido = Ticket::factory()->forAssignee($gestor)->create(['status' => 'resolved']);
    $finalizado = Ticket::factory()->forAssignee($gestor)->create(['status' => 'closed']);

    actedAsAdmin();

    removerResponsaveis()->assertOk()->assertJsonPath('data.affected', 0);

    expect($resolvido->fresh()->assignee_id)->toBe($gestor->id)
        ->and($finalizado->fresh()->assignee_id)->toBe($gestor->id);
});

it('conta apenas os chamados que tinham responsavel', function () {
    $gestor = novoGestor();

    Ticket::factory()->forAssignee($gestor)->create(['status' => 'open']);
    enfileirar(3, 'open'); // ja estavam sem responsavel

    actedAsAdmin();

    removerResponsaveis()->assertOk()->assertJsonPath('data.affected', 1);

    expect(Ticket::query()->whereNull('assignee_id')->count())->toBe(4);
});

it('nao faz nada quando nao ha nenhum responsavel para remover', function () {
    poolDeGestores(2);
    enfileirar(4);

    actedAsAdmin();

    removerResponsaveis()->assertOk()->assertJsonPath('data.affected', 0);
});

it('exige token para remover responsaveis', function () {
    $gestor = novoGestor();
    adicionarCarga($gestor, 3);

    removerResponsaveis()->assertUnauthorized();

    expect(cargaDe($gestor->id))->toBe(3);
});

it('recusa usuario comum na remocao', function () {
    $gestor = novoGestor();
    adicionarCarga($gestor, 3);

    Sanctum::actingAs(User::factory()->regular()->create());

    removerResponsaveis()->assertForbidden();

    expect(cargaDe($gestor->id))->toBe(3);
});

it('recusa gestor na remocao', function () {
    $gestor = novoGestor();
    adicionarCarga($gestor, 3);

    Sanctum::actingAs(novoGestor());

    removerResponsaveis()->assertForbidden();

    expect(cargaDe($gestor->id))->toBe(3);
});

it('redistribui a fila depois da remocao em lote', function () {
    $pool = poolDeGestores(2);
    [$a, $b] = array_keys($pool);

    adicionarCarga(User::find($a), 7);
    adicionarCarga(User::find($b), 3);

    actedAsAdmin();

    removerResponsaveis()->assertOk()->assertJsonPath('data.affected', 10);

    balancear()->assertOk()
        ->assertJsonPath('data.distributed', 10)
        ->assertJsonPath('data.difference', 0);

    expect(cargaDe($a))->toBe(5)
        ->and(cargaDe($b))->toBe(5);
});
