<?php

namespace App\Services;

use App\Models\Need;
use App\Models\NeedStage;
use App\Models\NeedStageGroup;
use Exception;
use Illuminate\Support\Collection;

readonly class NeedStageService
{
    /**
     * Team's stage groups with their stages, ordered. Seeds the default
     * pipeline the first time a team has none yet (brand new teams).
     */
    public function getAllGroups(): Collection
    {
        $teamId = auth()->user()->currentTeam->id;
        $this->ensureSeeded($teamId);

        return NeedStageGroup::where('team_id', $teamId)
            ->orderBy('position')
            ->with('stages')
            ->get();
    }

    /**
     * Seeds the default pipeline for a team with no stages yet. Exposed
     * separately (not just inlined in getAllGroups()) so other entry
     * points — e.g. creating a Need before the board has ever been loaded —
     * can't hit a "no stages" error.
     */
    public function ensureSeeded(int $teamId): void
    {
        if (!NeedStageGroup::where('team_id', $teamId)->exists()) {
            $this->seedDefaults($teamId);
        }
    }

    public function createGroup(array $data): NeedStageGroup
    {
        $teamId = auth()->user()->currentTeam->id;
        $position = (int) NeedStageGroup::where('team_id', $teamId)->max('position');

        return NeedStageGroup::create([
            'team_id' => $teamId,
            'label' => $data['label'] ?? 'Untitled group',
            'position' => $position + 1,
        ]);
    }

    /**
     * @throws Exception
     */
    public function updateGroup(array $data, int $id): NeedStageGroup
    {
        $group = $this->findGroup($id);
        $this->checkGroupPerms($group);

        $group->update([
            'label' => $data['label'] ?? $group->label,
        ]);

        return $group;
    }

    /**
     * @param array<int, int> $orderedIds
     */
    public function reorderGroups(array $orderedIds): void
    {
        $teamId = auth()->user()->currentTeam->id;

        $groups = NeedStageGroup::where('team_id', $teamId)
            ->whereIn('id', $orderedIds)
            ->get(['id', 'position'])
            ->keyBy('id');

        foreach (array_values($orderedIds) as $index => $id) {
            $group = $groups->get((int) $id);
            if (!$group || (int) $group->position === $index) {
                continue;
            }
            $group->update(['position' => $index]);
        }
    }

    /**
     * @throws Exception
     */
    public function destroyGroup(int $id): void
    {
        $group = $this->findGroup($id);
        $this->checkGroupPerms($group);

        if ($group->stages()->exists()) {
            throw new Exception('Move or delete this group\'s stages first');
        }

        $group->delete();
    }

    /**
     * @throws Exception
     */
    public function createStage(array $data): NeedStage
    {
        $teamId = auth()->user()->currentTeam->id;
        $group = $this->findGroup((int) ($data['need_stage_group_id'] ?? 0));
        $this->checkGroupPerms($group);

        $position = (int) NeedStage::where('need_stage_group_id', $group->id)->max('position');

        return NeedStage::create([
            'team_id' => $teamId,
            'need_stage_group_id' => $group->id,
            'label' => $data['label'] ?? 'Untitled stage',
            'color' => $data['color'] ?? '#9ca3af',
            'position' => $position + 1,
        ]);
    }

    /**
     * @throws Exception
     */
    public function updateStage(array $data, int $id): NeedStage
    {
        $stage = $this->findStage($id);
        $this->checkStagePerms($stage);

        $stage->update([
            'label' => $data['label'] ?? $stage->label,
            'color' => $data['color'] ?? $stage->color,
        ]);

        return $stage;
    }

    /**
     * @param array<int, int> $orderedIds
     *
     * @throws Exception
     */
    public function reorderStagesWithinGroup(int $groupId, array $orderedIds): void
    {
        $group = $this->findGroup($groupId);
        $this->checkGroupPerms($group);

        $stages = NeedStage::where('need_stage_group_id', $groupId)
            ->whereIn('id', $orderedIds)
            ->get(['id', 'position'])
            ->keyBy('id');

        foreach (array_values($orderedIds) as $index => $id) {
            $stage = $stages->get((int) $id);
            if (!$stage || (int) $stage->position === $index) {
                continue;
            }
            $stage->update(['position' => $index]);
        }
    }

    /**
     * Moves a stage to a different group (or just repositions it within its
     * current one if $groupId is unchanged) — appended to the end of the
     * target group unless $position is given.
     *
     * @throws Exception
     */
    public function moveStageToGroup(int $stageId, int $groupId, ?int $position = null): NeedStage
    {
        $stage = $this->findStage($stageId);
        $this->checkStagePerms($stage);
        $group = $this->findGroup($groupId);
        $this->checkGroupPerms($group);

        if ($position === null) {
            $position = (int) NeedStage::where('need_stage_group_id', $groupId)->max('position') + 1;
        }

        $stage->update([
            'need_stage_group_id' => $groupId,
            'position' => $position,
        ]);

        return $stage;
    }

    /**
     * @throws Exception
     */
    public function destroyStage(int $id): void
    {
        $stage = $this->findStage($id);
        $this->checkStagePerms($stage);

        $needsCount = Need::where('need_stage_id', $id)->count();
        if ($needsCount > 0) {
            throw new Exception("{$needsCount} need(s) are still in this stage — move them first");
        }

        $stage->delete();
    }

    private function seedDefaults(int $teamId): void
    {
        foreach (Need::DEFAULT_SEED as $groupPosition => $groupData) {
            $group = NeedStageGroup::create([
                'team_id' => $teamId,
                'label' => $groupData['label'],
                'position' => $groupPosition,
            ]);

            foreach ($groupData['stages'] as $stagePosition => $stageData) {
                NeedStage::create([
                    'team_id' => $teamId,
                    'need_stage_group_id' => $group->id,
                    'label' => $stageData['label'],
                    'color' => $stageData['color'],
                    'position' => $stagePosition,
                ]);
            }
        }
    }

    /**
     * @throws Exception
     */
    private function findGroup(int $id): NeedStageGroup
    {
        $group = NeedStageGroup::find($id);
        if (!$group) {
            throw new Exception('Stage group not found');
        }
        return $group;
    }

    /**
     * @throws Exception
     */
    private function findStage(int $id): NeedStage
    {
        $stage = NeedStage::find($id);
        if (!$stage) {
            throw new Exception('Stage not found');
        }
        return $stage;
    }

    /**
     * @throws Exception
     */
    private function checkGroupPerms(NeedStageGroup $group): void
    {
        if ((int) $group->team_id !== (int) auth()->user()->currentTeam->id) {
            throw new Exception('Not allowed');
        }
    }

    /**
     * @throws Exception
     */
    private function checkStagePerms(NeedStage $stage): void
    {
        if ((int) $stage->team_id !== (int) auth()->user()->currentTeam->id) {
            throw new Exception('Not allowed');
        }
    }
}
