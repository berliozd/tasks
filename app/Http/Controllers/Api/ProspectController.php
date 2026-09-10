<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\EmailNotFoundException;
use App\Http\Controllers\Controller;
use App\Services\ProspectService;
use Exception;
use Illuminate\Http\Request;
use InvalidArgumentException;

class ProspectController extends Controller
{
    public function __construct(private readonly ProspectService $prospectService)
    {
    }

    /**
     * @throws Exception
     */
    public function index(string $directoryId)
    {
        return $this->prospectService->getList((int) $directoryId);
    }

    /**
     * @throws Exception
     */
    public function tree(string $directoryId)
    {
        return $this->prospectService->getTreeList((int) $directoryId);
    }

    /**
     * @throws Exception
     */
    public function show(string $id)
    {
        return $this->prospectService->find((int) $id);
    }

    /**
     * @throws Exception
     */
    public function store(Request $request, string $directoryId)
    {
        return $this->prospectService->create([
            ...$request->toArray(),
            'directory_id' => (int) $directoryId,
        ]);
    }

    /**
     * @throws Exception
     */
    public function update(Request $request, string $id)
    {
        return $this->prospectService->update($request->toArray(), (int) $id);
    }

    /**
     * @throws Exception
     */
    public function destroy(string $id)
    {
        $this->prospectService->destroy((int) $id);
    }

    /**
     * @throws Exception
     */
    public function findEmail(string $id)
    {
        try {
            return $this->prospectService->findEmail((int) $id);
        } catch (EmailNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
