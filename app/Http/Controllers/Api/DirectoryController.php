<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DirectoryService;
use Exception;
use Illuminate\Http\Request;

class DirectoryController extends Controller
{
    public function __construct(private readonly DirectoryService $directoryService)
    {
    }

    public function index(Request $request)
    {
        $productId = $request->query('product_id');
        return $this->directoryService->getAll($productId !== null ? (int) $productId : null);
    }

    /**
     * @throws Exception
     */
    public function show(string $id)
    {
        return $this->directoryService->find((int) $id);
    }

    public function store(Request $request)
    {
        return $this->directoryService->create($request->toArray());
    }

    /**
     * @throws Exception
     */
    public function update(Request $request, string $id)
    {
        return $this->directoryService->update($request->toArray(), (int) $id);
    }

    /**
     * @throws Exception
     */
    public function destroy(string $id)
    {
        $this->directoryService->destroy((int) $id);
    }

    /**
     * @throws Exception
     */
    public function generate(Request $request, string $id)
    {
        $count = (int) $request->input('count', 5);
        return $this->directoryService->generate((int) $id, $count);
    }
}
