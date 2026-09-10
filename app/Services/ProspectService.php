<?php

namespace App\Services;

use App\Exceptions\EmailNotFoundException;
use App\Models\Directory;
use App\Models\Prospect;
use App\Models\ProspectAction;
use App\Repositories\DirectoryRepository;
use App\Repositories\ProspectRepository;
use App\Services\EmailFinder\EmailFinderInterface;
use Exception;
use Illuminate\Support\Collection;
use InvalidArgumentException;

readonly class ProspectService
{
    public function __construct(
        private ProspectRepository $prospectRepository,
        private DirectoryRepository $directoryRepository,
        private EmailFinderInterface $emailFinder,
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
     * Lightweight id/name list used by the prospection navigation tree —
     * avoids pulling every prospect's full details (and actions) just to
     * render a label in the sidebar.
     *
     * @throws Exception
     */
    public function getTreeList(int $directoryId): Collection
    {
        $this->checkDirectoryPerms($this->findDirectory($directoryId));
        return $this->prospectRepository->getTreeList($directoryId);
    }

    /**
     * @throws Exception
     */
    public function find(int $id): Prospect
    {
        $prospect = $this->findProspect($id);
        // Load directory (+ product) once, before the perm check, so
        // checkPerms() reuses this instead of triggering its own separate
        // lazy-load query for the same relation.
        $prospect->load(['directory:id,team_id,name,product_id', 'directory.product:id,name']);
        $this->checkPerms($prospect);

        $statusCounts = collect(ProspectAction::STATUSES)
            ->mapWithKeys(fn (string $status) => [
                "actions as {$status}_count" => fn ($query) => $query->where('status', $status),
            ])->all();
        $prospect->loadCount($statusCounts);

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
            'website' => array_key_exists('website', $data) ? $data['website'] : $prospect->website,
            'email' => array_key_exists('email', $data) ? $data['email'] : $prospect->email,
            'won' => array_key_exists('won', $data) ? (bool) $data['won'] : $prospect->won,
            'is_excluded' => array_key_exists('is_excluded', $data) ? (bool) $data['is_excluded'] : $prospect->is_excluded,
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
     * Best-effort scrape of the prospect's own website for a contact email —
     * a fallback for when AI generation/search didn't turn one up.
     *
     * @throws InvalidArgumentException if the prospect has no website to search
     * @throws EmailNotFoundException if the search turned up nothing — an
     *     expected, common outcome, not a failure, so it's a distinct type
     *     the controller maps to 404 instead of a generic 500
     * @throws Exception on any other failure (permissions, AI/API errors, ...)
     */
    public function findEmail(int $id): Prospect
    {
        $prospect = $this->findProspect($id);
        $this->checkPerms($prospect);

        if (empty($prospect->website)) {
            throw new InvalidArgumentException('This prospect has no website to search');
        }

        $email = $this->emailFinder->find($prospect->website);
        if (!$email) {
            throw new EmailNotFoundException('Could not find an email on this website');
        }

        return $this->prospectRepository->update($prospect, ['email' => $email]);
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
