<?php

namespace Tests\Feature;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_team_without_a_subscription_is_blocked_from_paid_features_but_keeps_tasks(): void
    {
        $user = User::factory()->withPersonalTeam(fn ($factory) => $factory->unsubscribed())->create();
        $this->actingAs($user);

        $this->assertFalse($user->currentTeam->is_pro);

        // Plain (non-Inertia, non-JSON) page visits blocked purely by the
        // paid-feature gate redirect to Billing instead of a bare 403 —
        // see EnsureFeatureEnabled.
        $this->get('/tasks')->assertSuccessful();
        $this->get('/products')->assertRedirect(route('billing'));
        $this->get('/documents')->assertRedirect(route('billing'));
        $this->get('/needs')->assertRedirect(route('billing'));

        $this->getJson('/api/tasks')->assertSuccessful();
        $this->getJson('/api/products')->assertForbidden();
        $this->getJson('/api/documents')->assertForbidden();
        $this->getJson('/api/needs')->assertForbidden();
    }

    public function test_a_subscribed_team_can_access_paid_features(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);

        $this->assertTrue($user->currentTeam->is_pro);

        $this->get('/products')->assertSuccessful();
        $this->get('/documents')->assertSuccessful();
        $this->get('/needs')->assertSuccessful();
    }

    public function test_a_manually_disabled_feature_stays_blocked_even_when_subscribed(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);
        $user->currentTeam->forceFill(['disabled_features' => ['needs']])->save();

        $this->assertTrue($user->currentTeam->is_pro);
        $this->get('/needs')->assertForbidden();
        $this->get('/documents')->assertSuccessful();
    }

    public function test_billing_page_reports_plan_status(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);

        $this->get('/billing')->assertSuccessful();
    }

    public function test_only_the_team_owner_can_start_checkout_or_open_the_billing_portal(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $member = User::factory()->create();
        $owner->currentTeam->users()->attach($member, ['role' => 'admin']);
        $member->switchTeam($owner->currentTeam);
        $this->actingAs($member);

        $this->get('/billing/checkout')->assertForbidden();
        $this->get('/billing/portal')->assertForbidden();
    }

    public function test_checkout_fails_clearly_if_the_team_is_already_subscribed(): void
    {
        config(['services.stripe.pro_price_id' => 'price_test']);

        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);

        $this->assertTrue($user->currentTeam->is_pro);
        $this->get('/billing/checkout')->assertServerError();
    }

    public function test_checkout_fails_clearly_when_stripe_is_not_configured(): void
    {
        config(['services.stripe.pro_price_id' => '']);

        $user = User::factory()->withPersonalTeam(fn ($factory) => $factory->unsubscribed())->create();
        $this->actingAs($user);

        $this->get('/billing/checkout')->assertServerError();
    }
}
