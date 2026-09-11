<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FeatureRequestService;
use Exception;
use Illuminate\Http\Request;

class FeatureRequestController extends Controller
{
    public function __construct(private readonly FeatureRequestService $featureRequestService)
    {
    }

    /**
     * @throws Exception
     */
    public function store(Request $request)
    {
        $data = $request->validate(['message' => 'required|string|max:2000']);

        $this->featureRequestService->submit($request->user(), $data['message']);

        return response()->json(['message' => 'Sent']);
    }
}
