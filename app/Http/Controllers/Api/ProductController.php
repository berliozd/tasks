<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ProductService;
use Exception;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(private readonly ProductService $productService)
    {
    }

    public function index()
    {
        return $this->productService->getAll();
    }

    public function tree()
    {
        return $this->productService->getTree();
    }

    /**
     * @throws Exception
     */
    public function show(string $id)
    {
        return $this->productService->find((int) $id);
    }

    public function store(Request $request)
    {
        return $this->productService->create($request->toArray());
    }

    /**
     * @throws Exception
     */
    public function update(Request $request, string $id)
    {
        return $this->productService->update($request->toArray(), (int) $id);
    }

    /**
     * @throws Exception
     */
    public function destroy(string $id)
    {
        $this->productService->destroy((int) $id);
    }
}
