<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ProspectActionService;
use Exception;
use Illuminate\Http\Request;

class ProspectActionController extends Controller
{
    public function __construct(private readonly ProspectActionService $prospectActionService)
    {
    }

    /**
     * @throws Exception
     */
    public function index(string $prospectId)
    {
        return $this->prospectActionService->getList((int) $prospectId);
    }

    /**
     * @throws Exception
     */
    public function store(Request $request, string $prospectId)
    {
        return $this->prospectActionService->create([
            ...$request->toArray(),
            'prospect_id' => (int) $prospectId,
        ]);
    }

    /**
     * @throws Exception
     */
    public function update(Request $request, string $id)
    {
        return $this->prospectActionService->update($request->toArray(), (int) $id);
    }

    /**
     * @throws Exception
     */
    public function destroy(string $id)
    {
        $this->prospectActionService->destroy((int) $id);
    }
}
