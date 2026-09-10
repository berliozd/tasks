<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Team;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;

class TeamInvitationController extends Controller
{
    /**
     * Signed accept-invitation links for a team's pending invitations, so a
     * PO can copy/paste one directly instead of relying solely on the
     * invitation email (which may not always be deliverable).
     */
    public function links(string $teamId)
    {
        $team = Team::findOrFail($teamId);
        Gate::forUser(auth()->user())->authorize('addTeamMember', $team);

        return $team->teamInvitations->map(fn ($invitation) => [
            'id' => $invitation->id,
            'url' => URL::signedRoute('team-invitations.accept', ['invitation' => $invitation->id]),
        ]);
    }
}
