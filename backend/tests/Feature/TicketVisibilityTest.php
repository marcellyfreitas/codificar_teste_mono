<?php

use App\Enums\UserRole;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function comum(): User
{
    return User::factory()->regular()->create();
}

function gestor(): User
{
    return User::factory()->gestor()->create();
}

function admin(): User
{
    return User::factory()->admin()->create();
}

/** Chamado do autor, com o responsável informado (ou sem ninguém, se omitido). */
function chamado(?User $autor = null, ?User $responsavel = null): Ticket
{
    return Ticket::factory()
        ->createdBy($autor ?? comum())
        ->forAssignee($responsavel ?? gestor())
        ->create();
}

function idsDaResposta($resposta): array
{
    return array_column($resposta->json('data') ?? [], 'id');
}

// ---------------------------------------------------------------- listagem

it('mostra ao usuario comum apenas os chamados que ele abriu', function () {
    $eu = comum();

    $meu = Ticket::factory()->createdBy($eu)->forAssignee(gestor())->create();
    Ticket::factory()->createdBy(comum())->forAssignee(gestor())->create();

    Sanctum::actingAs($eu);

    test()->getJson('/api/v1/tickets')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $meu->id);
});

it('mostra ao gestor apenas os chamados sob sua responsabilidade', function () {
    $eu = gestor();

    $meu = Ticket::factory()->createdBy(comum())->forAssignee($eu)->create();
    Ticket::factory()->createdBy(comum())->forAssignee(gestor())->create();

    Sanctum::actingAs($eu);

    test()->getJson('/api/v1/tickets')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $meu->id);
});

it('mostra ao admin todos os chamados', function () {
    $autor = comum();
    $gestor = gestor();

    Ticket::factory()->createdBy($autor)->forAssignee($gestor)->create();
    Ticket::factory()->createdBy(comum())->forAssignee(gestor())->create();
    Ticket::factory()->createdBy($autor)->unassigned()->create();

    Sanctum::actingAs(admin());

    test()->getJson('/api/v1/tickets')->assertOk()->assertJsonCount(3, 'data');
});

it('nao deixa o gestor ver pelo filtro o que nao esta com ele', function () {
    $eu = gestor();
    $outro = gestor();

    $meu = Ticket::factory()->createdBy(comum())->forAssignee($eu)->create();
    Ticket::factory()->createdBy(comum())->forAssignee($outro)->create();

    Sanctum::actingAs($eu);

    test()->getJson('/api/v1/tickets?assignee_id='.$outro->id)
        ->assertOk()
        ->assertJsonCount(0, 'data');

    test()->getJson('/api/v1/tickets?assignee_id='.$eu->id)
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('nao deixa o usuario comum ver pelo filtro o que outro abriu', function () {
    $eu = comum();
    $outro = comum();

    $meu = Ticket::factory()->createdBy($eu)->forAssignee(gestor())->create();
    Ticket::factory()->createdBy($outro)->forAssignee(gestor())->create();

    Sanctum::actingAs($eu);

    test()->getJson('/api/v1/tickets?user_id='.$outro->id)
        ->assertOk()
        ->assertJsonCount(0, 'data');

    test()->getJson('/api/v1/tickets?user_id='.$eu->id)
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('nao esconde do autor o proprio chamado que esta na fila', function () {
    // A amarra do autor é a autoria, não o responsável: um chamado sem responsável
    // continua visível para quem abriu, e some para quem não é o dono dele.
    $autor = comum();

    Ticket::factory()->createdBy($autor)->unassigned()->create();
    Ticket::factory()->createdBy(comum())->unassigned()->create();

    Sanctum::actingAs($autor);

    test()->getJson('/api/v1/tickets')->assertOk()->assertJsonCount(1, 'data');
});

it('esconde da fila os chamados sem responsavel, para quem so trata', function () {
    $gestor = gestor();

    Ticket::factory()->createdBy(comum())->unassigned()->create();
    Ticket::factory()->createdBy(comum())->forAssignee($gestor)->create();

    Sanctum::actingAs($gestor);

    test()->getJson('/api/v1/tickets')->assertOk()->assertJsonCount(1, 'data');
});

it('soma a visibilidade aos demais filtros em vez de substitui-los', function () {
    $eu = gestor();
    $meu = Ticket::factory()->createdBy(comum())->forAssignee($eu)->create([
        'status' => 'open',
        'title' => 'Impressora quebrada',
    ]);
    Ticket::factory()->createdBy(comum())->forAssignee($eu)->create([
        'status' => 'closed',
        'title' => 'Impressora resolvida',
    ]);
    Ticket::factory()->createdBy(comum())->forAssignee(gestor())->create([
        'status' => 'open',
        'title' => 'Impressora alheia',
    ]);

    Sanctum::actingAs($eu);

    test()->getJson('/api/v1/tickets?status=open')
        ->assertOk()
        ->assertJsonPath('data.0.id', $meu->id)
        ->assertJsonCount(1, 'data');
});

it('nao empurra a visibilidade para a pagina seguinte', function () {
    // O filtro precisa estar na consulta, e não depois da paginação: aplicá-lo no
    // collection devolveria uma página de 15 cheios de chamados alheios.
    $eu = gestor();

    foreach (range(1, 20) as $i) {
        Ticket::factory()->createdBy(comum())->forAssignee($eu)->create();
    }
    Ticket::factory()->createdBy(comum())->forAssignee(gestor())->create();

    Sanctum::actingAs($eu);

    test()->getJson('/api/v1/tickets?per_page=5')
        ->assertOk()
        ->assertJsonCount(5, 'data')
        ->assertJsonPath('total', 20);
});

it('nao expoe chamado excluido para ninguem', function () {
    $eu = gestor();

    $ticket = Ticket::factory()->createdBy(comum())->forAssignee($eu)->create();
    $ticket->delete();

    Sanctum::actingAs($eu);

    test()->getJson('/api/v1/tickets')->assertOk()->assertJsonCount(0, 'data');
});

// ------------------------------------------------------------ chamado unico

it('libera a leitura do proprio chamado para cada papel', function () {
    $comum = comum();
    $gestor = gestor();
    $admin = admin();

    $abertoPeloComum = Ticket::factory()->createdBy($comum)->forAssignee($gestor)->create();
    $tratadoPeloGestor = Ticket::factory()->createdBy($comum)->forAssignee($gestor)->create();
    $vistoPorTodos = Ticket::factory()->createdBy($comum)->forAssignee($gestor)->create();

    Sanctum::actingAs($comum);
    test()->getJson("/api/v1/tickets/{$abertoPeloComum->id}")->assertOk();

    Sanctum::actingAs($gestor);
    test()->getJson("/api/v1/tickets/{$tratadoPeloGestor->id}")->assertOk();

    Sanctum::actingAs($admin);
    test()->getJson("/api/v1/tickets/{$vistoPorTodos->id}")->assertOk();
});

it('nega a leitura de chamado alheio com 403 e mensagem', function (string $papel, string $mensagem) {
    $ticket = chamado(comum(), gestor());

    Sanctum::actingAs($papel === 'comum' ? comum() : gestor());

    test()->getJson("/api/v1/tickets/{$ticket->id}")
        ->assertForbidden()
        ->assertJsonPath('message', $mensagem);
})->with([
    'gestor ve o que e de outro' => ['gestor', 'Gestores só veem os chamados sob sua responsabilidade.'],
    'comum ve o que outro abriu' => ['comum', 'Você só vê os chamados que abriu.'],
]);

it('nega com 403 a leitura alheia pelo id, e nao com 500', function () {
    Sanctum::actingAs(comum());

    test()->getJson('/api/v1/tickets/'.chamado()->id)
        ->assertForbidden()
        ->assertJsonStructure(['message']);
});

it('nega a leitura de chamado na fila para gestor, e nao casa null com id', function () {
    // `assignee_id` anulável: a comparação da policy não pode transformar
    // "sem responsável" em "responsável de ID 0" e liberar a fila para o gestor.
    $gestor = gestor();

    $naFila = Ticket::factory()->createdBy(comum())->unassigned()->create();

    Sanctum::actingAs($gestor);

    test()->getJson("/api/v1/tickets/{$naFila->id}")->assertForbidden();
    test()->getJson("/api/v1/tickets/{$naFila->id}")
        ->assertJsonPath('message', 'Gestores só veem os chamados sob sua responsabilidade.');
});

it('libera a leitura do chamado na fila para o admin', function () {
    $naFila = Ticket::factory()->createdBy(comum())->unassigned()->create();

    Sanctum::actingAs(admin());

    test()->getJson("/api/v1/tickets/{$naFila->id}")->assertOk();
});

it('exige token para ler um chamado', function () {
    test()->getJson('/api/v1/tickets/'.chamado()->id)->assertUnauthorized();
});

it('mantem 404 para chamado inexistente', function () {
    Sanctum::actingAs(comum());

    test()->getJson('/api/v1/tickets/999999')->assertNotFound();
});

// ------------------------------------------------------------------ matriz

it('aplica a matriz de visibilidade por papel', function (UserRole $papel, bool $veAutor, bool $veResponsavel) {
    $quem = match ($papel) {
        UserRole::USER => comum(),
        UserRole::GESTOR => gestor(),
        UserRole::ADMIN => admin(),
    };

    $comoAutor = Ticket::factory()->createdBy($quem)->forAssignee(gestor())->create();
    $comoResponsavel = Ticket::factory()->createdBy(comum())->forAssignee($quem)->create();

    Sanctum::actingAs($quem);

    $ids = idsDaResposta(test()->getJson('/api/v1/tickets?per_page=100'));

    expect(in_array($comoAutor->id, $ids, true))->toBe($veAutor)
        ->and(in_array($comoResponsavel->id, $ids, true))->toBe($veResponsavel);
})->with([
    // O gestor não vê o que abriu para outra pessoa: a amarra é o responsável.
    'user: so o que abriu' => [UserRole::USER, true, false],
    'gestor: so o que trata' => [UserRole::GESTOR, false, true],
    'admin: tudo' => [UserRole::ADMIN, true, true],
]);
