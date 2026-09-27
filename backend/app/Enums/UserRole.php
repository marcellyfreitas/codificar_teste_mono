<?php

namespace App\Enums;

enum UserRole: string
{
    case USER = 'user';
    case GESTOR = 'gestor';
    case ADMIN = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::USER => 'Usuário',
            self::GESTOR => 'Gestor',
            self::ADMIN => 'Administrador',
        };
    }

    public function isAdmin(): bool
    {
        return $this === self::ADMIN;
    }

    public function isManager(): bool
    {
        return $this === self::ADMIN || $this === self::GESTOR;
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
