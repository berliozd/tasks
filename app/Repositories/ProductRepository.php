<?php

namespace App\Repositories;

use App\Models\Product;

readonly class ProductRepository
{
    public function create(array $data): Product
    {
        return Product::create($data);
    }

    public function find(int $id): ?Product
    {
        return Product::find($id);
    }

    public function update(Product $product, array $data): Product
    {
        $product->fill($data);
        $product->save();
        return $product;
    }

    public function destroy(Product $product): void
    {
        $product->delete();
    }
}
