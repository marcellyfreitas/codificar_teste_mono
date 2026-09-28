<?php

namespace App\Rules;

use App\Enums\UserRole;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ExistsAsGestor implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null) {
            return;
        }

        $isGestor = User::query()
            ->whereKey($value)
            ->where('role', UserRole::GESTOR->value)
            ->exists();

        if (! $isGestor) {
            $fail('O responsável deve ser um usuário gestor.');
        }
    }
}
