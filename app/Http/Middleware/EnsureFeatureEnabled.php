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

        if (!$team || $team->hasFeatureEnabled($feature)) {
            return $next($request);
        }

        $manuallyDisabled = in_array($feature, $team->disabled_features ?? [], true);
        $isPlainPageVisit = !$request->header('X-Inertia') && !$request->expectsJson();

        // A team blocked only by the paid-feature gate (not an admin's own
        // toggle) gets sent to Billing on a direct page visit instead of a
        // bare error page. API calls and Inertia client-side navigation
        // (which shouldn't happen anyway, since the nav hides these links)
        // keep the plain 403.
        if (!$manuallyDisabled && $isPlainPageVisit) {
            return redirect()->route('billing')
                ->with('message', "Upgrade to Pro to unlock \"{$feature}\".");
        }

        abort(403, "The \"{$feature}\" feature has been disabled for your team.");
    }
}
