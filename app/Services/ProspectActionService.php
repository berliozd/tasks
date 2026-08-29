<?php

namespace App\Services;

use App\Models\Directory;
use App\Models\Prospect;
use App\Models\ProspectAction;
use App\Repositories\DirectoryRepository;
use App\Repositories\ProspectActionRepository;
use App\Repositories\ProspectRepository;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;

readonly class ProspectActionService
{
    private const TYPES = ['email', 'call', 'linkedin', 'meeting', 'other'];
    private const STATUSES = ['planned', 'sent', 'replied', 'bounced', 'no_response', 'won', 'lost'];

    public function __construct(
        private ProspectActionRepository $prospectActionRepository,
        private ProspectRepository $prospectRepository,
        private DirectoryRepository $directoryRepository,
    ) {
    }

    /**
     * @throws Exception
     */
    public function getList(int $prospectId): Collection
    {
        $this->checkProspectPerms($this->findProspect($prospectId));
        return $this->prospectActionRepository->getList($prospectId);
    }

    /**
     * @throws Exception
     */
    public function create(array $data): ProspectAction
    {
        $prospect = $this->findProspect((int) ($data['prospect_id'] ?? 0));
        $this->checkProspectPerms($prospect);

        $type = $data['type'] ?? 'email';
        $status = $data['status'] ?? 'planned';
        $this->validateType($type);
        $this->validateStatus($status);

        return $this->prospectActionRepository->create([
            'prospect_id' => $prospect->id,
            'type' => $type,
            'message' => $data['message'] ?? null,
            'status' => $status,
            'scheduled_at' => !empty($data['scheduled_at']) ? Carbon::parse($data['scheduled_at']) : now(),
        ]);
    }

    /**
     * @throws Exception
     */
    public function update(array $data, int $id): ProspectAction
    {
        $action = $this->findAction($id);
        $this->checkPerms($action);

        $update = [];
        if (array_key_exists('type', $data)) {
            $this->validateType($data['type']);
            $update['type'] = $data['type'];
        }
        if (array_key_exists('status', $data)) {
            $this->validateStatus($data['status']);
            $update['status'] = $data['status'];
        }
        if (array_key_exists('message', $data)) {
            $update['message'] = $data['message'];
        }
        if (array_key_exists('scheduled_at', $data)) {
            $update['scheduled_at'] = !empty($data['scheduled_at']) ? Carbon::parse($data['scheduled_at']) : null;
        }

        return $this->prospectActionRepository->update($action, $update);
    }

    /**
     * @throws Exception
     */
    public function destroy(int $id): void
    {
        $action = $this->findAction($id);
        $this->checkPerms($action);
        $this->prospectActionRepository->destroy($action);
    }

    /**
     * @throws Exception
     */
    private function validateType(string $type): void
    {
        if (!in_array($type, self::TYPES, true)) {
            throw new Exception('Invalid type');
        }
    }

    /**
     * @throws Exception
     */
    private function validateStatus(string $status): void
    {
        if (!in_array($status, self::STATUSES, true)) {
            throw new Exception('Invalid status');
        }
    }

    /**
     * @throws Exception
     */
    private function findProspect(int $prospectId): Prospect
    {
        $prospect = $this->prospectRepository->find($prospectId);
        if (!$prospect) {
            throw new Exception('Prospect not found');
        }
        return $prospect;
    }

    /**
     * @throws Exception
     */
    private function findAction(int $id): ProspectAction
    {
        $action = $this->prospectActionRepository->find($id);
        if (!$action) {
            throw new Exception('Action not found');
        }
        return $action;
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
    private function checkProspectPerms(Prospect $prospect): void
    {
        $directory = $prospect->directory ?? $this->directoryRepository->find($prospect->directory_id);
        if (!$directory) {
            throw new Exception('Directory not found');
        }
        $this->checkDirectoryPerms($directory);
    }

    /**
     * @throws Exception
     */
    private function checkPerms(ProspectAction $action): void
    {
        $prospect = $action->prospect ?? $this->findProspect($action->prospect_id);
        $this->checkProspectPerms($prospect);
    }
}
