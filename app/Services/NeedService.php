<?php

namespace App\Services;

use App\Models\Need;
use App\Models\NeedActivity;
use App\Models\NeedStage;
use App\Repositories\NeedRepository;
use Exception;
use Illuminate\Support\Collection;

readonly class NeedService
{
    public function __construct(
        private NeedRepository $needRepository,
        private NeedStageService $needStageService,
    ) {
    }

    public function getAll(): Collection
    {
        return Need::where('team_id', auth()->user()->currentTeam->id)
            ->orderBy('position')
            ->get();
    }

    /**
     * @throws Exception
     */
    public function find(int $id): Need
    {
        $need = $this->findNeed($id);
        $this->checkPerms($need);
        $need->load(['activities' => fn ($query) => $query->with('user')->latest()]);
        return $need;
    }

    /**
     * @throws Exception
     */
    public function create(array $data): Need
    {
        $teamId = auth()->user()->currentTeam->id;
        $firstStage = $this->firstStage($teamId);

        $position = (int) Need::where('team_id', $teamId)
            ->where('need_stage_id', $firstStage->id)
            ->max('position');

        $need = $this->needRepository->create([
            'team_id' => $teamId,
            'title' => $data['title'] ?? 'Untitled need',
            'description' => $data['description'] ?? null,
            'confluence_url' => $data['confluence_url'] ?? null,
            'jira_key' => $data['jira_key'] ?? null,
            'jira_url' => $data['jira_url'] ?? null,
            'business_owner' => $data['business_owner'] ?? null,
            'need_stage_id' => $firstStage->id,
            'position' => $position + 1,
        ]);

        NeedActivity::create([
            'need_id' => $need->id,
            'user_id' => auth()->id(),
            'type' => 'created',
        ]);

        return $need;
    }

    /**
     * Title/description/reference fields only — never touches stage, which
     * is always changed via moveStage() so every stage change is logged.
     *
     * @throws Exception
     */
    public function update(array $data, int $id): Need
    {
        $need = $this->findNeed($id);
        $this->checkPerms($need);

        $this->needRepository->update($need, [
            'title' => $data['title'] ?? $need->title,
            'description' => array_key_exists('description', $data) ? $data['description'] : $need->description,
            'confluence_url' => array_key_exists('confluence_url', $data) ? $data['confluence_url'] : $need->confluence_url,
            'jira_key' => array_key_exists('jira_key', $data) ? $data['jira_key'] : $need->jira_key,
            'jira_url' => array_key_exists('jira_url', $data) ? $data['jira_url'] : $need->jira_url,
            'business_owner' => array_key_exists('business_owner', $data) ? $data['business_owner'] : $need->business_owner,
        ]);

        return $need;
    }

    /**
     * @throws Exception
     */
    public function moveStage(int $id, int $stageId): Need
    {
        $need = $this->findNeed($id);
        $this->checkPerms($need);

        $newStage = NeedStage::find($stageId);
        if (!$newStage || (int) $newStage->team_id !== (int) $need->team_id) {
            throw new Exception('Invalid stage');
        }

        $fromStage = $need->stage;

        $position = (int) Need::where('team_id', $need->team_id)
            ->where('need_stage_id', $stageId)
            ->max('position');

        $this->needRepository->update($need, [
            'need_stage_id' => $stageId,
            'position' => $position + 1,
        ]);

        if ($fromStage->id !== $newStage->id) {
            NeedActivity::create([
                'need_id' => $need->id,
                'user_id' => auth()->id(),
                'type' => 'stage_changed',
                'from_stage' => $fromStage->label,
                'to_stage' => $newStage->label,
            ]);
        }

        return $need;
    }

    /**
     * @param array<int, int> $ids
     *
     * @throws Exception
     */
    public function reorderWithinStage(int $stageId, array $ids): void
    {
        $teamId = auth()->user()->currentTeam->id;

        $needs = Need::where('team_id', $teamId)
            ->where('need_stage_id', $stageId)
            ->whereIn('id', $ids)
            ->get(['id', 'position'])
            ->keyBy('id');

        foreach (array_values($ids) as $index => $id) {
            $need = $needs->get((int) $id);
            if (!$need || (int) $need->position === $index) {
                continue;
            }
            $need->update(['position' => $index]);
        }
    }

    /**
     * @throws Exception
     */
    public function addNote(int $id, string $note): Need
    {
        $need = $this->findNeed($id);
        $this->checkPerms($need);

        $note = trim($note);
        if ($note === '') {
            throw new Exception('Note cannot be empty');
        }

        NeedActivity::create([
            'need_id' => $need->id,
            'user_id' => auth()->id(),
            'type' => 'note',
            'note' => $note,
        ]);

        $need->load(['activities' => fn ($query) => $query->with('user')->latest()]);
        return $need;
    }

    /**
     * @throws Exception
     */
    public function destroy(int $id): void
    {
        $need = $this->findNeed($id);
        $this->checkPerms($need);
        $this->needRepository->destroy($need);
    }

    /**
     * @return array{count: int, recent: Collection}
     */
    public function getDashboardSummary(): array
    {
        $teamId = auth()->user()->currentTeam->id;

        return [
            'count' => Need::where('team_id', $teamId)->count(),
            'recent' => Need::where('team_id', $teamId)
                ->with('stage:id,label,color')
                ->orderByDesc('updated_at')
                ->limit(5)
                ->get(['id', 'title', 'need_stage_id', 'updated_at']),
        ];
    }

    /**
     * @throws Exception
     */
    private function findNeed(int $id): Need
    {
        $need = $this->needRepository->find($id);
        if (!$need) {
            throw new Exception('Need not found');
        }
        return $need;
    }

    /**
     * @throws Exception
     */
    private function firstStage(int $teamId): NeedStage
    {
        $this->needStageService->ensureSeeded($teamId);

        $stage = NeedStage::where('need_stages.team_id', $teamId)
            ->join('need_stage_groups', 'need_stage_groups.id', '=', 'need_stages.need_stage_group_id')
            ->orderBy('need_stage_groups.position')
            ->orderBy('need_stages.position')
            ->select('need_stages.*')
            ->first();

        if (!$stage) {
            throw new Exception('This team has no stages yet — open the pipeline settings first');
        }

        return $stage;
    }

    /**
     * @throws Exception
     */
    private function checkPerms(Need $need): void
    {
        if ((int) $need->team_id !== (int) auth()->user()->currentTeam->id) {
            throw new Exception('Not allowed');
        }
    }
}
