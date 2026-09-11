<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureFeatureEnabled
{
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $team = $request->user()?->currentTeam;

        if ($team && !$team->hasFeatureEnabled($feature)) {
            abort(403, "The \"{$feature}\" feature has been disabled for your team.");
        }

        return $next($request);
    }
}
