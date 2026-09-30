<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public const REGULAR_USERS = 100;

    public const GESTOR_USERS = 10;

    public const ADMIN_USERS = 3;

    public const TEST_USER_EMAIL = 'test@example.com';

    public const GESTOR_EMAIL = 'gestor@example.com';

    public const ADMIN_EMAIL = 'admin@example.com';

    public function run(): void
    {
        $this->contaConhecida(self::TEST_USER_EMAIL, 'Test User', UserRole::USER);
        $this->contaConhecida(self::GESTOR_EMAIL, 'Test Gestor', UserRole::GESTOR);
        $this->contaConhecida(self::ADMIN_EMAIL, 'Test Admin', UserRole::ADMIN);

        $comuns = max(self::REGULAR_USERS - 1, 0);

        if ($comuns > 0) {
            User::factory()->count($comuns)->regular()->create();
        }

        $gestores = max(self::GESTOR_USERS - 1, 0);

        if ($gestores > 0) {
            User::factory()->count($gestores)->gestor()->create();
        }

        $admins = max(self::ADMIN_USERS - 1, 0);

        if ($admins > 0) {
            User::factory()->count($admins)->admin()->create();
        }

        $this->command?->info(sprintf(
            'Usuarios: %d comuns + %d gestores + %d administradores = %d total.',
            User::where('role', UserRole::USER->value)->count(),
            User::where('role', UserRole::GESTOR->value)->count(),
            User::where('role', UserRole::ADMIN->value)->count(),
            User::count(),
        ));

        $this->command?->info(sprintf(
            'Contas conhecidas (senha "password"): %s | %s | %s',
            self::TEST_USER_EMAIL,
            self::GESTOR_EMAIL,
            self::ADMIN_EMAIL,
        ));
    }

    private function contaConhecida(string $email, string $name, UserRole $papel): void
    {
        User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => 'password',
                'email_verified_at' => now(),
                'role' => $papel->value,
            ],
        );
    }
}
