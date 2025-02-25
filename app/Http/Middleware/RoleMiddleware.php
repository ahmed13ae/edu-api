<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request and check if the user has the required role.
     *
     * @param Request $request The HTTP request instance.
     * @param Closure $next The next middleware or request handler.
     * @param string $role The required role to access the route.
     * @return Response The response, allowing or denying access.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException If the user is unauthorized.
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        // Check if the user is authenticated and has the required role
        if (!$request->user() || $request->user()->role !== $role) {
            return response()->json(["message" => "Unauthorized!"], 403);
        }

        // Proceed with the request
        return $next($request);
    }
}
