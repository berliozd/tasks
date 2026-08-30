<?php

namespace App\Http\Controllers;

use App\Services\ProductService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProductsController extends Controller
{
    public function __construct(private readonly ProductService $productService)
    {
    }

    public function __invoke(Request $request)
    {
        return Inertia::render(
            'Products/Products', [
                'products' => $this->productService->getAll()->toArray(),
            ]
        );
    }
}
