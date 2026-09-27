<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

/**
 * O usuario que autentica tambem aparece no diretorio que ele consulta, entao
 * os testes que contam clientes tem de contar ele. O nome e parametrizado
 * porque a ordenacao por nome precisa de um valor conhecido.
 */
function acessaComoAdminDoDiretorio(string $name = 'Administrador do Diretorio'): User
{
    $admin = User::factory()->admin()->create(['name' => $name]);

    Sanctum::actingAs($admin);

    return $admin;
}

it('recusa o diretorio sem token', function () {
    User::factory()->count(3)->create();

    test()->getJson('/api/v1/users')->assertUnauthorized();
});

it('lista usuarios dos tres papeis quando nenhum filtro e informado', function () {
    User::factory()->regular()->create();
    User::factory()->gestor()->create();

    // O proprio admin autenticado conta como o segundo administrador.
    acessaComoAdminDoDiretorio();

    test()->getJson('/api/v1/users')
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonPath('total', 3)
        ->assertJsonPath('data.0.role', UserRole::ADMIN->value);
});

it('filtra o diretorio por papel', function (UserRole $role, int $esperados) {
    User::factory()->regular()->count(2)->create();
    User::factory()->gestor()->count(3)->create();

    // O admin autenticado entra no total quando o filtro e o proprio papel dele.
    $admin = acessaComoAdminDoDiretorio();

    $total = $role === UserRole::ADMIN ? $esperados + 1 : $esperados;

    $response = test()->getJson('/api/v1/users?role='.$role->value)
        ->assertOk()
        ->assertJsonPath('total', $total);

    $todosGestores = collect($response->json('data'))
        ->every(fn (array $usuario) => $usuario['role'] === $role->value);

    expect($todosGestores)->toBeTrue()
        ->and($response->json('total'))->toBe($total)
        ->and($admin->role)->toBe(UserRole::ADMIN);
})->with([
    'usuario' => [UserRole::USER, 2],
    'gestor' => [UserRole::GESTOR, 3],
    'admin' => [UserRole::ADMIN, 0],
]);

it('devolve no filtro de gestor exatamente quem a regra de responsavel aceita', function () {
    User::factory()->regular()->count(2)->create();
    User::factory()->gestor()->count(4)->create();
    User::factory()->admin()->count(2)->create();

    acessaComoAdminDoDiretorio();

    $listados = test()->getJson('/api/v1/users?role='.UserRole::GESTOR->value)
        ->assertOk()
        ->json('data');

    // `ExistsAsGestor` aceita so `role = gestor`. Se o diretorio offering mais
    // que isso, o seletor de responsavel da UI passa a oferecer um admin e o
    // POST volta 422.
    $aceitos = User::query()
        ->where('role', UserRole::GESTOR->value)
        ->pluck('id')
        ->sort()
        ->values()
        ->all();

    $oferecidos = collect($listados)->pluck('id')->sort()->values()->all();

    expect($oferecidos)->toBe($aceitos);
});

it('recusa um papel que nao existe no enum', function () {
    acessaComoAdminDoDiretorio();

    test()->getJson('/api/v1/users?role=supervisor')
        ->assertStatus(422)
        ->assertJsonValidationErrors('role')
        ->assertJsonPath('errors.role.0', 'O valor fornecido para papel é inválido.');
});

it('busca por nome ou por e-mail', function () {
    User::factory()->create(['name' => 'Maria Aparecida', 'email' => 'maria.aparecida@empresa.com']);
    User::factory()->create(['name' => 'Joao Batista', 'email' => 'joao.batista@empresa.com']);
    User::factory()->create(['name' => 'Carla Mendes', 'email' => 'carla.mendes@empresa.com']);

    acessaComoAdminDoDiretorio();

    test()->getJson('/api/v1/users?search=Maria')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Maria Aparecida');

    test()->getJson('/api/v1/users?search=joao.batista')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Joao Batista');
});

it('ignora busca vazia em vez de devolver ninguem', function () {
    User::factory()->count(2)->create();

    acessaComoAdminDoDiretorio();

    test()->getJson('/api/v1/users?search=')
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

it('combina filtro de papel com busca', function () {
    User::factory()->gestor()->create(['name' => 'Gestor Alvo']);
    User::factory()->regular()->create(['name' => 'Gestor Alvo']);

    acessaComoAdminDoDiretorio();

    test()->getJson('/api/v1/users?role='.UserRole::GESTOR->value.'&search=Alvo')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.role', UserRole::GESTOR->value);
});

it('limita a pagina ao mesmo teto dos chamados, em vez de recusar', function (string $informado, int $esperado) {
    User::factory()->count(120)->create();

    acessaComoAdminDoDiretorio();

    test()->getJson('/api/v1/users?per_page='.$informado)
        ->assertOk()
        ->assertJsonPath('per_page', $esperado);
})->with([
    'acima do teto cai para 100' => ['200', 100],
    'abaixo do piso sobe para 1' => ['0', 1],
    'negativo sobe para 1' => ['-5', 1],
    'dentro do limite passa direto' => ['50', 50],
]);

it('nunca devolve a senha nem o remember token', function () {
    User::factory()->create();

    acessaComoAdminDoDiretorio();

    $usuario = test()->getJson('/api/v1/users')->assertOk()->json('data.0');

    expect($usuario)->not->toHaveKey('password')
        ->and($usuario)->not->toHaveKey('remember_token')
        ->and($usuario)->toHaveKeys(['id', 'name', 'email', 'role']);
});

it('ordena o diretorio por nome para a lista ser estavel', function () {
    User::factory()->create(['name' => 'Zelia']);
    User::factory()->create(['name' => 'Amalia']);
    User::factory()->create(['name' => 'Benedita']);

    acessaComoAdminDoDiretorio('Waldemar');

    $nomes = collect(test()->getJson('/api/v1/users')->assertOk()->json('data'))
        ->pluck('name')
        ->all();

    expect($nomes)->toBe(['Amalia', 'Benedita', 'Waldemar', 'Zelia']);
});

it('atende o diretorio tambem para usuario comum, porque chamado ja expoe quem abriu', function () {
    User::factory()->gestor()->create(['name' => 'Gestor Visivel']);

    Sanctum::actingAs(User::factory()->regular()->create());

    test()->getJson('/api/v1/users?role='.UserRole::GESTOR->value)
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Gestor Visivel');
});
