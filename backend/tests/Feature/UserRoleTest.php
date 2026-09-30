<?php

use App\Enums\UserRole;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function insertUserWithRawRole(string $role): string
{
    $email = uniqid().'@example.com';

    DB::table('users')->insert([
        'name' => 'Bruto',
        'email' => $email,
        'email_verified_at' => now(),
        'password' => bcrypt('password'),
        'role' => $role,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return $email;
}

it('persiste todos os valores do enum na coluna do banco', function (string $role) {
    $email = insertUserWithRawRole($role);

    expect(DB::table('users')->where('email', $email)->value('role'))->toBe($role);
})->with(fn () => UserRole::values());

it('rejeita um valor de papel que nao existe no enum', function () {
    insertUserWithRawRole('inexistente');
})->throws(QueryException::class);

it('converte a coluna role para o enum UserRole', function () {
    $email = insertUserWithRawRole(UserRole::GESTOR->value);

    $user = User::query()->where('email', $email)->firstOrFail();

    expect($user->role)->toBeInstanceOf(UserRole::class)
        ->and($user->role)->toBe(UserRole::GESTOR);
});

it('classifica gestor como gestor, mas nao como administrador', function () {
    $user = User::factory()->create();
    $user->forceFill(['role' => UserRole::GESTOR])->save();

    expect($user->fresh()->isAdmin())->toBeFalse()
        ->and($user->fresh()->isManager())->toBeTrue();
});

it('classifica administrador como administrador e como gestor', function () {
    expect(User::factory()->admin()->create()->isAdmin())->toBeTrue()
        ->and(User::factory()->admin()->create()->isManager())->toBeTrue();
});

it('classifica usuario comum como nao gestor e nao administrador', function () {
    $user = User::factory()->regular()->create();

    expect($user->isAdmin())->toBeFalse()
        ->and($user->isManager())->toBeFalse();
});

it('devolve um rotulo para todos os papeis', function (UserRole $role) {
    expect($role->label())->not->toBe('');
})->with(UserRole::cases());

it('aplica o papel correto em cada estado da factory', function () {
    expect(User::factory()->regular()->create()->role)->toBe(UserRole::USER)
        ->and(User::factory()->gestor()->create()->role)->toBe(UserRole::GESTOR)
        ->and(User::factory()->admin()->create()->role)->toBe(UserRole::ADMIN);
});

it('ignora o papel enviado no cadastro da API', function (string $role) {
    $this->postJson('/api/v1/register', [
        'name' => 'Tentativa',
        'email' => 'tentativa@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => $role,
    ])->assertCreated();

    expect(User::query()->where('email', 'tentativa@example.com')->value('role'))
        ->toBe(UserRole::USER);
})->with(fn () => UserRole::values());

it('cria a quantidade de usuarios de cada papel no seeder', function () {
    $this->seed(UserSeeder::class);

    $count = fn (UserRole $role) => User::query()->where('role', $role->value)->count();

    expect($count(UserRole::USER))->toBe(UserSeeder::REGULAR_USERS)
        ->and($count(UserRole::GESTOR))->toBe(UserSeeder::GESTOR_USERS)
        ->and($count(UserRole::ADMIN))->toBe(UserSeeder::ADMIN_USERS)
        ->and(User::count())->toBe(
            UserSeeder::REGULAR_USERS + UserSeeder::GESTOR_USERS + UserSeeder::ADMIN_USERS
        );
});

it('cria pelo menos a quantidade de gestores do seeder', function () {
    $this->seed(UserSeeder::class);

    expect(User::query()->where('role', UserRole::GESTOR->value)->count())
        ->toBeGreaterThanOrEqual(UserSeeder::GESTOR_USERS);
});

it('mantem a conta de teste da colecao Postman', function () {
    $this->seed(UserSeeder::class);

    $conta = User::query()->where('email', UserSeeder::TEST_USER_EMAIL)->first();

    expect($conta)->not->toBeNull()
        ->and($conta->role)->toBe(UserRole::USER);
});

it('mantem as contas conhecidas de gestor e admin para a colecao Postman', function () {
    $this->seed(UserSeeder::class);

    $gestor = User::query()->where('email', UserSeeder::GESTOR_EMAIL)->first();
    $admin = User::query()->where('email', UserSeeder::ADMIN_EMAIL)->first();

    expect($gestor)->not->toBeNull()
        ->and($gestor->role)->toBe(UserRole::GESTOR)
        ->and($admin)->not->toBeNull()
        ->and($admin->role)->toBe(UserRole::ADMIN);
});

it('nao estoura a quantidade de cada papel ao incluir as contas conhecidas', function () {
    $this->seed(UserSeeder::class);

    expect(User::where('role', UserRole::USER->value)->count())->toBe(UserSeeder::REGULAR_USERS)
        ->and(User::where('role', UserRole::GESTOR->value)->count())->toBe(UserSeeder::GESTOR_USERS)
        ->and(User::where('role', UserRole::ADMIN->value)->count())->toBe(UserSeeder::ADMIN_USERS);
});

it('inclui gestores no pool da atribuicao automatica', function () {
    $gestor = User::factory()->create();
    $gestor->forceFill(['role' => UserRole::GESTOR])->save();

    $ocupado = User::factory()->create();
    Ticket::factory()->count(3)->forAssignee($ocupado)->create(['status' => 'open']);

    $this->actingAs($ocupado)
        ->postJson('/api/v1/tickets', [
            'title' => 'Chamado que deve ir para o gestor',
            'description' => 'O gestor esta ocioso; o usuario comum esta sobrecarregado.',
            'priority' => 'low',
        ])
        ->assertCreated()
        ->assertJsonPath('data.assignee_id', $gestor->id);
});
