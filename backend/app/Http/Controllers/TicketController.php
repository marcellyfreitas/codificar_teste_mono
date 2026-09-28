<?php

namespace App\Http\Controllers;

use App\Actions\BalanceUnassignedTickets;
use App\Actions\UnassignOpenTickets;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateTicketRequest;
use App\Models\Ticket;
use App\Services\TicketService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TicketController extends Controller
{
    public function __construct(
        protected TicketService $ticketService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'status',
            'priority',
            'user_id',
            'assignee_id',
            'search',
            'created_from',
            'created_to',
        ]);

        $perPage = min(max($request->integer('per_page', 15), 1), 100);

        $tickets = $this->ticketService->list($filters, $perPage);

        return response()->json($tickets, Response::HTTP_OK);
    }

    public function store(StoreTicketRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $autoAssign = $request->boolean('auto_assign', true);
            $authorId = $request->user()->getKey();

            unset($data['auto_assign']);

            $ticket = $this->ticketService->store($data, $authorId, $autoAssign);

            return response()->json([
                'message' => 'Chamado criado com sucesso.',
                'data' => $ticket->load(['user', 'assignee']),
            ], Response::HTTP_CREATED);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Erro ao criar chamado.',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show(Ticket $ticket): JsonResponse
    {
        return response()->json([
            'data' => $ticket->load(['user', 'assignee']),
        ], Response::HTTP_OK);
    }

    public function update(UpdateTicketRequest $request, Ticket $ticket): JsonResponse
    {
        $this->authorize('update', $ticket);

        try {
            $updatedTicket = $this->ticketService->update($ticket, $request->validated());

            return response()->json([
                'message' => 'Chamado atualizado com sucesso.',
                'data' => $updatedTicket->load(['user', 'assignee']),
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Erro ao atualizar chamado.',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy(Ticket $ticket): JsonResponse
    {
        $this->authorize('delete', $ticket);

        try {
            $this->ticketService->destroy($ticket);

            return response()->json(null, Response::HTTP_NO_CONTENT);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Erro ao excluir chamado.',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function balance(BalanceUnassignedTickets $balance): JsonResponse
    {
        $this->authorize('balance');

        try {
            return response()->json([
                'message' => 'Redistribuição concluída.',
                'data' => $balance(),
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Erro ao redistribuir os chamados.',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function unassignOpen(UnassignOpenTickets $unassign): JsonResponse
    {
        $this->authorize('unassign-open');

        try {
            return response()->json([
                'message' => 'Responsáveis removidos dos chamados em aberto.',
                'data' => [
                    'affected' => $unassign(),
                ],
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Erro ao remover os responsáveis.',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
