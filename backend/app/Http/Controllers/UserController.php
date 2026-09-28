<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListUsersRequest;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class UserController extends Controller
{
    public function __construct(
        protected UserService $userService
    ) {}

    /**
     * Nao ha policy aqui de proposito. Todo papel autenticado ja ve nome e
     * e-mail de quem aparece em qualquer chamado, via o `user` embutido no
     * payload de `/tickets`. Restringir o diretorio nao protegeria nada que o
     * payload de chamado ja nao exponha.
     */
    public function index(ListUsersRequest $request): JsonResponse
    {
        $filters = $request->only(['role', 'search']);

        $perPage = min(max($request->integer('per_page', 15), 1), 100);

        return response()->json($this->userService->list($filters, $perPage), Response::HTTP_OK);
    }
}
