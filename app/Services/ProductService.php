<?php

namespace App\Services;

use App\Models\Product;
use App\Repositories\ProductRepository;
use Exception;
use Illuminate\Support\Collection;

readonly class ProductService
{
    public function __construct(
        private ProductRepository $productRepository,
    ) {
    }

    public function getAll(): Collection
    {
        return Product::where('team_id', auth()->user()->currentTeam->id)
            ->withCount('directories')
            ->get();
    }

    /**
     * Lightweight product/directory nesting used to render the prospection
     * navigation tree — just ids and names, no counts or extra relations.
     */
    public function getTree(): Collection
    {
        return Product::where('team_id', auth()->user()->currentTeam->id)
            ->with(['directories' => fn ($query) => $query->select('id', 'product_id', 'name')->orderBy('name')])
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * @throws Exception
     */
    public function find(int $id): Product
    {
        $product = $this->productRepository->find($id);
        if (!$product) {
            throw new Exception('Product not found');
        }
        $this->checkPerms($product);
        $product->load(['directories' => fn ($query) => $query->withCount('prospects')]);
        return $product;
    }

    public function create(array $data): Product
    {
        $data['team_id'] = auth()->user()->currentTeam->id;
        return $this->productRepository->create($data);
    }

    /**
     * @throws Exception
     */
    public function update(array $data, int $id): Product
    {
        $product = $this->productRepository->find($id);
        if (!$product) {
            throw new Exception('Product not found');
        }
        $this->checkPerms($product);
        return $this->productRepository->update($product, $data);
    }

    /**
     * @throws Exception
     */
    public function destroy(int $id): void
    {
        $product = $this->productRepository->find($id);
        if (!$product) {
            throw new Exception('Product not found');
        }
        $this->checkPerms($product);
        $this->productRepository->destroy($product);
    }

    /**
     * @throws Exception
     */
    private function checkPerms(Product $product): void
    {
        if ((int) $product->team_id !== (int) auth()->user()->currentTeam->id) {
            throw new Exception('Not allowed');
        }
    }
}
