<?php

namespace App\Http\Middleware;

use App\Modules\Identity\Enums\Permission;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

final class EnsureAdministrationAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        Gate::authorize(Permission::AdministrationAccess->value);

        return $next($request);
    }
}
