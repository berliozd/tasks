<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class BillingController extends Controller
{
    public function show(Request $request): Response
    {
        $team = $request->user()->currentTeam;

        return Inertia::render('Billing', [
            'isPro' => $team->subscribed(),
            'onGracePeriod' => $team->subscription()?->onGracePeriod() ?? false,
            'endsAt' => $team->subscription()?->ends_at,
            'canManageBilling' => Gate::forUser($request->user())->allows('update', $team),
        ]);
    }

    /**
     * @throws Exception
     */
    public function checkout(Request $request)
    {
        $team = $request->user()->currentTeam;
        Gate::forUser($request->user())->authorize('update', $team);

        $priceId = (string) config('services.stripe.pro_price_id');
        if (empty($priceId)) {
            throw new Exception('Stripe is not configured yet (missing STRIPE_PRO_PRICE_ID)');
        }

        if ($team->subscribed()) {
            throw new Exception('This team already has an active subscription');
        }

        return $team->newSubscription('default', $priceId)->checkout([
            'success_url' => route('billing') . '?checkout=success',
            'cancel_url' => route('billing') . '?checkout=cancelled',
            'allow_promotion_codes' => true,
        ]);
    }

    /**
     * @throws Exception
     */
    public function portal(Request $request)
    {
        $team = $request->user()->currentTeam;
        Gate::forUser($request->user())->authorize('update', $team);

        return $team->redirectToBillingPortal(route('billing'));
    }
}
