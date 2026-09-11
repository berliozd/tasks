<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * The pipeline every team using Needs already has today, as plain
     * arrays — seeded verbatim here for teams with existing data, and
     * reused by Need::DEFAULT_SEED for teams created after this migration.
     */
    private const GROUPS = [
        ['label' => 'Before Dev Pipeline', 'stages' => [
            ['key' => 'discovery', 'label' => 'Discovery', 'color' => '#9ca3af'],
            ['key' => 'documented', 'label' => 'Documented', 'color' => '#818cf8'],
            ['key' => 'jira_created', 'label' => 'Jira ticket created', 'color' => '#60a5fa'],
            ['key' => 'validated', 'label' => 'Validated with business', 'color' => '#22d3ee'],
        ]],
        ['label' => 'In Dev Pipeline', 'stages' => [
            ['key' => 'grooming', 'label' => 'Grooming', 'color' => '#c084fc'],
            ['key' => 'todo', 'label' => 'To do', 'color' => '#fbbf24'],
            ['key' => 'dev', 'label' => 'Dev', 'color' => '#fb923c'],
            ['key' => 'qa', 'label' => 'QA', 'color' => '#f472b6'],
        ]],
        ['label' => 'After Dev Pipeline', 'stages' => [
            ['key' => 'soon', 'label' => 'Coming soon', 'color' => '#2dd4bf'],
            ['key' => 'prod', 'label' => 'In production', 'color' => '#4ade80'],
        ]],
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $teamIds = DB::table('needs')->whereNull('need_stage_id')->distinct()->pluck('team_id');

        foreach ($teamIds as $teamId) {
            $stageIdsByKey = $this->seedTeam((int) $teamId);

            foreach ($stageIdsByKey as $key => $stageId) {
                DB::table('needs')
                    ->where('team_id', $teamId)
                    ->where('stage', $key)
                    ->update(['need_stage_id' => $stageId]);
            }
        }

        // Guard against any row whose old `stage` value didn't match a
        // known key (shouldn't happen) — fall back to the team's first
        // stage rather than leave it null.
        DB::table('needs')->whereNull('need_stage_id')->get(['id', 'team_id'])->each(function ($need) {
            $fallbackStageId = DB::table('need_stages')
                ->where('team_id', $need->team_id)
                ->orderBy('position')
                ->value('id');
            if ($fallbackStageId) {
                DB::table('needs')->where('id', $need->id)->update(['need_stage_id' => $fallbackStageId]);
            }
        });

        Schema::table('needs', function (Blueprint $table) {
            $table->foreignId('need_stage_id')->nullable(false)->change();
            $table->dropColumn('stage');
        });
    }

    /**
     * @return array<string, int> stage key => new need_stages.id
     */
    private function seedTeam(int $teamId): array
    {
        $stageIdsByKey = [];

        foreach (self::GROUPS as $groupPosition => $group) {
            $groupId = DB::table('need_stage_groups')->insertGetId([
                'team_id' => $teamId,
                'label' => $group['label'],
                'position' => $groupPosition,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($group['stages'] as $stagePosition => $stage) {
                $stageIdsByKey[$stage['key']] = DB::table('need_stages')->insertGetId([
                    'team_id' => $teamId,
                    'need_stage_group_id' => $groupId,
                    'label' => $stage['label'],
                    'color' => $stage['color'],
                    'position' => $stagePosition,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        return $stageIdsByKey;
    }

    /**
     * Reverse the migrations.
     *
     * Best-effort only: the old `stage` string column is restored empty —
     * this app has no production data depending on a precise rollback of
     * this one-way schema change.
     */
    public function down(): void
    {
        Schema::table('needs', function (Blueprint $table) {
            $table->string('stage')->nullable()->after('need_stage_id');
            $table->foreignId('need_stage_id')->nullable()->change();
        });
    }
};
