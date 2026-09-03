<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DocumentService;

class DocumentFlagController extends Controller
{
    public function __construct(private readonly DocumentService $documentService)
    {
    }

    public function index()
    {
        return $this->documentService->getAllFlags();
    }
}
