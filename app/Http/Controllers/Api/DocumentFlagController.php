<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DocumentService;
use Exception;

class DocumentFlagController extends Controller
{
    public function __construct(private readonly DocumentService $documentService)
    {
    }

    public function index()
    {
        return $this->documentService->getAllFlags();
    }

    /**
     * @throws Exception
     */
    public function destroy(string $id)
    {
        $this->documentService->deleteFlag((int) $id);
    }
}
