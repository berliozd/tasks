<?php

namespace App\Services;

use App\Models\Directory;
use App\Models\Prospect;
use App\Models\ProspectAction;
use App\Repositories\DirectoryRepository;
use App\Repositories\ProspectRepository;
use Exception;
use Illuminate\Support\Collection;

readonly class ProspectService
{
    public function __construct(
        private ProspectRepository $prospectRepository,
        private DirectoryRepository $directoryRepository,
    ) {
    }

    /**
     * @throws Exception
     */
    public function getList(int $directoryId): Collection
    {
        $this->checkDirectoryPerms($this->findDirectory($directoryId));
        return $this->prospectRepository->getList($directoryId);
    }

    /**
     * @throws Exception
     */
    public function find(int $id): Prospect
    {
        $prospect = $this->findProspect($id);
        $this->checkPerms($prospect);

        $statusCounts = collect(ProspectAction::STATUSES)
            ->mapWithKeys(fn (string $status) => [
                "actions as {$status}_count" => fn ($query) => $query->where('status', $status),
            ])->all();
        $prospect->loadCount($statusCounts);
        $prospect->load(['directory:id,name,product_id', 'directory.product:id,name']);

        return $prospect;
    }

    /**
     * @throws Exception
     */
    public function create(array $data): Prospect
    {
        $directory = $this->findDirectory((int) ($data['directory_id'] ?? 0));
        $this->checkDirectoryPerms($directory);

        return $this->prospectRepository->create([
            'directory_id' => $directory->id,
            'name' => $data['name'] ?? '',
            'website' => $data['website'] ?? null,
            'email' => $data['email'] ?? null,
        ]);
    }

    /**
     * @throws Exception
     */
    public function update(array $data, int $id): Prospect
    {
        $prospect = $this->findProspect($id);
        $this->checkPerms($prospect);
        return $this->prospectRepository->update($prospect, [
            'name' => $data['name'] ?? $prospect->name,
            'website' => $data['website'] ?? null,
            'email' => $data['email'] ?? null,
            'won' => array_key_exists('won', $data) ? (bool) $data['won'] : $prospect->won,
        ]);
    }

    /**
     * @throws Exception
     */
    public function destroy(int $id): void
    {
        $prospect = $this->findProspect($id);
        $this->checkPerms($prospect);
        $this->prospectRepository->destroy($prospect);
    }

    /**
     * @throws Exception
     */
    private function findDirectory(int $directoryId): Directory
    {
        $directory = $this->directoryRepository->find($directoryId);
        if (!$directory) {
            throw new Exception('Directory not found');
        }
        return $directory;
    }

    /**
     * @throws Exception
     */
    private function findProspect(int $id): Prospect
    {
        $prospect = $this->prospectRepository->find($id);
        if (!$prospect) {
            throw new Exception('Prospect not found');
        }
        return $prospect;
    }

    /**
     * @throws Exception
     */
    private function checkDirectoryPerms(Directory $directory): void
    {
        if ((int) $directory->team_id !== (int) auth()->user()->currentTeam->id) {
            throw new Exception('Not allowed');
        }
    }

    /**
     * @throws Exception
     */
    private function checkPerms(Prospect $prospect): void
    {
        $directory = $prospect->directory ?? $this->findDirectory($prospect->directory_id);
        $this->checkDirectoryPerms($directory);
    }
}
