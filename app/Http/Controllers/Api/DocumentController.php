<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DocumentService;
use Exception;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    public function __construct(private readonly DocumentService $documentService)
    {
    }

    public function index(Request $request)
    {
        $flagIds = $request->query('flag_ids');
        $flagIds = is_array($flagIds) ? array_map('intval', $flagIds) : null;
        return $this->documentService->getAll($flagIds);
    }

    /**
     * @throws Exception
     */
    public function show(string $id)
    {
        return $this->documentService->find((int) $id);
    }

    public function store(Request $request)
    {
        return $this->documentService->create($request->toArray());
    }

    /**
     * @throws Exception
     */
    public function update(Request $request, string $id)
    {
        return $this->documentService->update($request->toArray(), (int) $id);
    }

    /**
     * @throws Exception
     */
    public function updateFlags(Request $request, string $id)
    {
        return $this->documentService->updateFlags((array) $request->input('flags', []), (int) $id);
    }

    /**
     * @throws Exception
     */
    public function rescanFlags(string $id)
    {
        return $this->documentService->rescanFlags((int) $id);
    }

    /**
     * @throws Exception
     */
    public function destroy(string $id)
    {
        $this->documentService->destroy((int) $id);
    }
}
