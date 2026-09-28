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

    public function index(ListUsersRequest $request): JsonResponse
    {
        $filters = $request->only(['role', 'search']);

        $perPage = min(max($request->integer('per_page', 15), 1), 100);

        return response()->json($this->userService->list($filters, $perPage), Response::HTTP_OK);
    }
}
