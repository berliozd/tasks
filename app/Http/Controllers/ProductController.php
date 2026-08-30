<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class ProductController extends Controller
{
    public function __invoke(Request $request, string $product)
    {
        return Inertia::render(
            'Products/Show', [
                'productId' => (int) $product,
            ]
        );
    }
}
