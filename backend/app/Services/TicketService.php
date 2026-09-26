<?php

namespace App\Services;

use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Models\Ticket;
use App\Models\User;
use DateTimeImmutable;
use Exception;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TicketService
{
    public function list(array $filters, User $viewer, int $perPage = 15): LengthAwarePaginator
    {
        $query = Ticket::with(['user', 'assignee'])->visibleTo($viewer);
        $status = $this->filterValue($filters, 'status');
        $priority = $this->filterValue($filters, 'priority');
        $search = $this->filterValue($filters, 'search');
        $userId = $this->filterValue($filters, 'user_id');
        $assigneeId = $this->filterValue($filters, 'assignee_id');
        $createdFrom = $this->filterDate($filters, 'created_from');
        $createdTo = $this->filterDate($filters, 'created_to');

        if ($status !== null) {
            $query->where('status', $status);
        }

        if ($priority !== null) {
            $query->where('priority', $priority);
        }

        if ($userId !== null && ctype_digit($userId)) {
            $query->where('user_id', (int) $userId);
        }

        if ($assigneeId !== null && ctype_digit($assigneeId)) {
            $query->where('assignee_id', (int) $assigneeId);
        }

        if ($search !== null) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($createdFrom !== null) {
            $query->whereDate('created_at', '>=', $createdFrom);
        }

        if ($createdTo !== null) {
            $query->whereDate('created_at', '<=', $createdTo);
        }

        return $query->latest()->paginate($perPage);
    }

    public function store(array $data, int $authorId, bool $autoAssign = true): Ticket
    {
        return DB::transaction(function () use ($data, $authorId, $autoAssign) {
            try {
                $data['user_id'] = $authorId;

                if ($autoAssign && empty($data['assignee_id'])) {
                    $data['assignee_id'] = $this->autoAssignUser();
                }

                $data['assignee_id'] ??= null;

                return Ticket::create($data);
            } catch (Exception $e) {
                Log::error('Erro ao criar chamado: '.$e->getMessage());
                throw $e;
            }
        });
    }

    public function update(Ticket $ticket, array $data): Ticket
    {
        return DB::transaction(function () use ($ticket, $data) {
            try {
                unset($data['user_id']);

                $ticket->update($data);

                return $ticket->fresh();
            } catch (Exception $e) {
                Log::error("Erro ao atualizar o chamado #{$ticket->id}: ".$e->getMessage());
                throw $e;
            }
        });
    }

    public function destroy(Ticket $ticket): bool
    {
        return DB::transaction(function () use ($ticket) {
            try {
                return $ticket->delete();
            } catch (Exception $e) {
                Log::error("Erro ao deletar o chamado #{$ticket->id}: ".$e->getMessage());
                throw $e;
            }
        });
    }

    public function autoAssignUser(): ?int
    {
        return User::query()
            ->where('role', UserRole::GESTOR->value)
            ->select('users.id')
            ->withCount([
                'assignedTickets' => function ($query) {
                    $query->whereIn('status', TicketStatus::openStatuses());
                },
            ])
            ->orderBy('assigned_tickets_count', 'asc')
            ->orderBy('users.id', 'asc')
            ->lockForUpdate()
            ->first()
            ?->id;
    }

    public function balanceUnassignedTickets(): array
    {
        return DB::transaction(function () {
            try {
                $candidates = $this->balanceCandidates();

                if ($candidates === []) {
                    return $this->balanceReport(0, 0, []);
                }

                $loads = $this->currentLoads($candidates);
                $pending = Ticket::query()
                    ->whereNull('assignee_id')
                    ->whereIn('status', TicketStatus::openStatuses())
                    ->orderBy('created_at')
                    ->orderBy('id')
                    ->get(['id']);

                $leftBehind = Ticket::query()
                    ->whereNull('assignee_id')
                    ->whereNotIn('status', TicketStatus::openStatuses())
                    ->count();

                $assigned = 0;

                foreach ($pending as $ticket) {
                    $target = $this->lightestId($loads);

                    if ($target === null) {
                        break;
                    }

                    $ticket->assignee_id = $target;
                    $ticket->save();
                    $loads[$target]++;
                    $assigned++;
                }

                return $this->balanceReport($assigned, $leftBehind, $loads);
            } catch (Exception $e) {
                Log::error('Erro ao redistribuir chamados: '.$e->getMessage());
                throw $e;
            }
        }, 3);
    }

    public function unassignOpenTickets(): int
    {
        return DB::transaction(function () {
            try {
                return Ticket::query()
                    ->whereIn('status', TicketStatus::openStatuses())
                    ->whereNotNull('assignee_id')
                    ->update(['assignee_id' => null]);
            } catch (Exception $e) {
                Log::error('Erro ao remover responsaveis: '.$e->getMessage());
                throw $e;
            }
        });
    }

    private function balanceCandidates(): array
    {
        return User::query()
            ->where('role', UserRole::GESTOR->value)
            ->orderBy('id')
            ->lockForUpdate()
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function currentLoads(array $candidates): array
    {
        $loads = array_fill_keys($candidates, 0);

        $counts = Ticket::query()
            ->whereIn('assignee_id', $candidates)
            ->whereIn('status', TicketStatus::openStatuses())
            ->selectRaw('assignee_id, COUNT(*) AS total')
            ->groupBy('assignee_id')
            ->pluck('total', 'assignee_id');

        foreach ($counts as $userId => $total) {
            $loads[(int) $userId] = (int) $total;
        }

        return $loads;
    }

    private function lightestId(array $loads): ?int
    {
        $bestId = null;
        $bestLoad = null;

        foreach ($loads as $id => $load) {
            if ($bestLoad === null || $load < $bestLoad) {
                $bestId = $id;
                $bestLoad = $load;
            }
        }

        return $bestId;
    }

    private function balanceReport(int $assigned, int $leftBehind, array $loads): array
    {
        ksort($loads);

        $values = $loads === [] ? [0] : array_values($loads);

        return [
            'distributed' => $assigned,
            'kept_open_untouched' => $leftBehind,
            'gestores' => count($loads),
            'min_open' => min($values),
            'max_open' => max($values),
            'difference' => max($values) - min($values),
            'load_by_gestor' => $loads,
        ];
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

    private function filterDate(array $filters, string $key): ?string
    {
        $value = $this->filterValue($filters, $key);

        if ($value === null) {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        if ($date === false || $date->format('Y-m-d') !== $value) {
            return null;
        }

        return $value;
    }
}
