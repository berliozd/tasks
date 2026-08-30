<?php

namespace App\Services;

use App\Models\Directory;
use App\Models\Product;
use App\Models\Prospect;
use App\Models\ProspectAction;
use App\Repositories\DirectoryRepository;
use App\Repositories\ProspectRepository;
use App\Services\ProspectGenerator\ProspectGeneratorInterface;
use Exception;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

readonly class DirectoryService
{
    public function __construct(
        private DirectoryRepository $directoryRepository,
        private ProspectRepository $prospectRepository,
        private ProspectGeneratorInterface $prospectGenerator,
    ) {
    }

    public function getAll(?int $productId = null): Collection
    {
        return Directory::where('team_id', auth()->user()->currentTeam->id)
            ->when($productId, fn ($query) => $query->where('product_id', $productId))
            ->withCount('prospects')
            ->get();
    }

    /**
     * @throws Exception
     */
    public function find(int $id): Directory
    {
        $directory = $this->directoryRepository->find($id);
        if (!$directory) {
            throw new Exception('Directory not found');
        }
        $this->checkPerms($directory);
        // Prospects' actions are lazy-loaded per-prospect via the dedicated
        // endpoint (Partials/ProspectActions.vue) rather than eager-loaded
        // here, so a directory with many prospects stays cheap to open.
        // Cheap per-status counts are still eager-loaded so the list can show
        // a breakdown like "2 sent, 1 replied" instead of a raw total.
        $statusCounts = collect(ProspectAction::STATUSES)
            ->mapWithKeys(fn (string $status) => [
                "actions as {$status}_count" => fn ($query) => $query->where('status', $status),
            ])->all();
        $directory->load(['product', 'prospects' => fn ($query) => $query->withCount($statusCounts)]);
        return $directory;
    }

    /**
     * @throws Exception
     */
    public function create(array $data): Directory
    {
        $teamId = auth()->user()->currentTeam->id;
        $product = Product::find($data['product_id'] ?? null);
        if (!$product || (int) $product->team_id !== (int) $teamId) {
            throw new Exception('Product not found');
        }
        $data['team_id'] = $teamId;
        return $this->directoryRepository->create($data);
    }

    /**
     * @throws Exception
     */
    public function update(array $data, int $id): Directory
    {
        $directory = $this->directoryRepository->find($id);
        if (!$directory) {
            throw new Exception('Directory not found');
        }
        $this->checkPerms($directory);
        return $this->directoryRepository->update($directory, $data);
    }

    /**
     * @throws Exception
     */
    public function destroy(int $id): void
    {
        $directory = $this->directoryRepository->find($id);
        if (!$directory) {
            throw new Exception('Directory not found');
        }
        $this->checkPerms($directory);
        $this->directoryRepository->destroy($directory);
    }

    /**
     * @throws Exception
     */
    public function generate(int $id, int $count): array
    {
        $directory = $this->directoryRepository->find($id);
        if (!$directory) {
            throw new Exception('Directory not found');
        }
        $this->checkPerms($directory);

        if (empty($directory->prompt)) {
            throw new Exception('Directory has no prompt to generate from');
        }

        $count = max(1, min(50, $count));

        $existingNames = $directory->prospects()->pluck('name')->all();

        // Emails are checked across every directory under this same product,
        // not just the current one, so the same contact doesn't get generated
        // twice into two different directories of the same product.
        $productDirectoryIds = Directory::where('product_id', $directory->product_id)->pluck('id');
        $existingEmails = Prospect::whereIn('directory_id', $productDirectoryIds)
            ->whereNotNull('email')
            ->pluck('email')
            ->all();
        $seenEmails = array_map(fn (string $email) => mb_strtolower(trim($email)), $existingEmails);

        $rows = $this->prospectGenerator->generate($directory->prompt, $count, $existingNames);

        $candidateUrls = collect($rows)->pluck('website')->filter()->unique()->values()->all();
        $reachable = $this->reachableWebsites($candidateUrls);

        // Tallied so the frontend can explain *why* fewer prospects came back
        // than requested — a generation that silently returns nothing is
        // confusing otherwise.
        $skippedIncomplete = 0;
        $skippedDuplicate = 0;
        $skippedUnreachable = 0;

        $created = collect($rows)
            ->filter(function (array $row) use (&$seenEmails, $reachable, &$skippedIncomplete, &$skippedDuplicate, &$skippedUnreachable) {
                $name = mb_strtolower(trim((string) ($row['name'] ?? '')));
                $email = mb_strtolower(trim((string) ($row['email'] ?? '')));
                // Require a contact email, skip anything whose email is already used
                // elsewhere in the product (or repeated within this same generated
                // batch), and — since the AI can still claim a website that doesn't
                // actually exist — drop any prospect whose website didn't come back
                // with a real 200 when we checked it.
                if ($name === '' || $email === '') {
                    $skippedIncomplete++;
                    return false;
                }
                if (in_array($email, $seenEmails, true)) {
                    $skippedDuplicate++;
                    return false;
                }
                if (!empty($row['website']) && !($reachable[$row['website']] ?? false)) {
                    $skippedUnreachable++;
                    return false;
                }
                $seenEmails[] = $email;
                return true;
            })
            ->map(fn (array $row) => $this->prospectRepository->create([
                'directory_id' => $directory->id,
                'name' => $row['name'],
                'website' => $row['website'] ?? null,
                'email' => $row['email'],
            ]))
            ->values();

        return [
            'requested' => $count,
            'created_count' => $created->count(),
            'skipped_duplicate_count' => $skippedDuplicate,
            'skipped_unreachable_count' => $skippedUnreachable,
            'skipped_incomplete_count' => $skippedIncomplete,
            'prospects' => $created,
        ];
    }

    /**
     * Actually visit each candidate URL (concurrently, so checking several
     * doesn't multiply the wait) and return [url => bool] for whether it
     * genuinely responded 200 — the AI can claim a real-looking website that
     * doesn't actually exist or answer, so this is a real check, not a guess.
     *
     * @param array<int, string> $urls
     * @return array<string, bool>
     */
    private function reachableWebsites(array $urls): array
    {
        if (empty($urls)) {
            return [];
        }

        $responses = Http::pool(fn ($pool) => collect($urls)->map(
            fn (string $url) => $pool->as($url)
                ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; ProspectionBot/1.0)'])
                ->timeout(5)
                ->connectTimeout(3)
                ->get($url)
        )->all());

        return collect($urls)->mapWithKeys(function (string $url) use ($responses) {
            $response = $responses[$url] ?? null;
            return [$url => $response instanceof Response && $response->status() === 200];
        })->all();
    }

    /**
     * @throws Exception
     */
    private function checkPerms(Directory $directory): void
    {
        if ((int) $directory->team_id !== (int) auth()->user()->currentTeam->id) {
            throw new Exception('Not allowed');
        }
    }
}
