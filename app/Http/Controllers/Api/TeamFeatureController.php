<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Services\TeamFeatureService;
use Illuminate\Http\Request;

class TeamFeatureController extends Controller
{
    public function __construct(private readonly TeamFeatureService $teamFeatureService)
    {
    }

    public function index(Request $request)
    {
        return [
            'features' => Team::FEATURES,
            'disabled' => $this->teamFeatureService->getDisabled($request->user()->currentTeam),
        ];
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'disabled' => ['array'],
            'disabled.*' => ['string', 'in:' . implode(',', Team::FEATURES)],
        ]);

        $disabled = $this->teamFeatureService->update(
            $request->user(),
            $request->user()->currentTeam,
            $data['disabled'] ?? [],
        );

        return ['disabled' => $disabled];
    }
}
