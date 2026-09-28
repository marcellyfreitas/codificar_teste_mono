<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class UserService
{
    /**
     * Diretorio de pessoas. O filtro por papel existe porque o seletor de
     * responsavel do cliente so pode oferecer quem a regra `ExistsAsGestor`
     * aceita: `role = gestor`. Um administrador listado como responsavel vira
     * 422 na hora de salvar.
     */
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = User::query();

        $role = $this->filterValue($filters, 'role');
        $search = $this->filterValue($filters, 'search');

        if ($role !== null) {
            $query->where('role', $role);
        }

        if ($search !== null) {
            $query->where(function (Builder $query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('name')->orderBy('id')->paginate($perPage);
    }

    private function filterValue(array $filters, string $key): ?string
    {
        $value = $filters[$key] ?? null;

        if (! is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
