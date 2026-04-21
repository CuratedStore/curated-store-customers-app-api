<?php

namespace App\Http\Middleware;

use App\Support\CustomersDataStore;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiTokenAuth
{
    public function __construct(private readonly CustomersDataStore $store)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $token = (string) $request->bearerToken();
        if ($token === '') {
            return new JsonResponse(['message' => 'Unauthenticated.'], 401);
        }

        $data = $this->store->read();
        $userId = $data['tokens'][$token] ?? null;
        if (!$userId) {
            return new JsonResponse(['message' => 'Invalid or expired token.'], 401);
        }

        $user = collect($data['users'])->firstWhere('id', (int) $userId);
        if (!$user) {
            return new JsonResponse(['message' => 'User not found for token.'], 401);
        }

        $request->attributes->set('auth_token', $token);
        $request->attributes->set('auth_user', $user);

        return $next($request);
    }
}
