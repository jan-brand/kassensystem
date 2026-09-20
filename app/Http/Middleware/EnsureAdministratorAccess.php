<?php

namespace App\Http\Middleware;

use App\Modules\Identity\Enums\UserRole;
use App\Modules\Identity\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureAdministratorAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (
            ! $user instanceof User
            || ! $user->active
            || $user->role !== UserRole::Administrator
        ) {
            abort(403);
        }

        return $next($request);
    }
}
