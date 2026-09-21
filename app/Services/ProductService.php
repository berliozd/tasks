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
        private ProspectionSummaryService $prospectionSummaryService,
    ) {
    }

    public function getAll(): Collection
    {
        $products = Product::where('team_id', auth()->user()->currentTeam->id)
            ->withCount(['directories', 'prospects'])
            ->get();

        $actionStatusCountsByProduct = $this->prospectionSummaryService->getActionStatusCountsByProduct($products->pluck('id'));
        return $products->map(function (Product $product) use ($actionStatusCountsByProduct) {
            $product->action_status_counts = $actionStatusCountsByProduct->get($product->id, []);
            return $product;
        });
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

    /**
     * Identifies each product on the "Actions completed over time" chart —
     * same palette the backfill migration used for existing products.
     */
    private const COLOR_PALETTE = [
        '#158749', '#2563eb', '#d97706', '#dc2626', '#7c3aed',
        '#0891b2', '#db2777', '#65a30d', '#ea580c', '#4338ca',
    ];

    public function create(array $data): Product
    {
        $teamId = auth()->user()->currentTeam->id;
        $data['team_id'] = $teamId;
        $data['color'] = $data['color'] ?? $this->nextColor($teamId);
        return $this->productRepository->create($data);
    }

    private function nextColor(int $teamId): string
    {
        $count = Product::where('team_id', $teamId)->count();
        return self::COLOR_PALETTE[$count % count(self::COLOR_PALETTE)];
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
