<?php

namespace App\Services;

use App\Models\Directory;
use App\Repositories\DirectoryRepository;
use App\Repositories\ProspectRepository;
use App\Services\ProspectGenerator\ProspectGeneratorInterface;
use Exception;
use Illuminate\Support\Collection;

readonly class DirectoryService
{
    public function __construct(
        private DirectoryRepository $directoryRepository,
        private ProspectRepository $prospectRepository,
        private ProspectGeneratorInterface $prospectGenerator,
    ) {
    }

    public function getAll(): Collection
    {
        return Directory::where('team_id', auth()->user()->currentTeam->id)
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
        // A cheap count is still eager-loaded so the list can show "N actions".
        $directory->load(['prospects' => fn ($query) => $query->withCount('actions')]);
        return $directory;
    }

    public function create(array $data): Directory
    {
        $data['team_id'] = auth()->user()->currentTeam->id;
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
    public function generate(int $id, int $count): Collection
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
        $seenNames = array_map(fn (string $name) => mb_strtolower(trim($name)), $existingNames);

        $rows = $this->prospectGenerator->generate($directory->prompt, $count, $existingNames);

        $created = collect($rows)
            ->filter(function (array $row) use (&$seenNames) {
                $name = mb_strtolower(trim((string) ($row['name'] ?? '')));
                // Require a contact email, and skip anything already in the directory
                // (or repeated within this same generated batch).
                if ($name === '' || empty($row['email']) || in_array($name, $seenNames, true)) {
                    return false;
                }
                $seenNames[] = $name;
                return true;
            })
            ->map(fn (array $row) => $this->prospectRepository->create([
                'directory_id' => $directory->id,
                'name' => $row['name'],
                'website' => $row['website'] ?? null,
                'email' => $row['email'],
            ]));

        return $created;
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
