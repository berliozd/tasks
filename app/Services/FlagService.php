<?php

namespace App\Services;

use App\Models\Flag;
use App\Repositories\FlagRepository;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

readonly class FlagService
{

    public function __construct(private FlagRepository $flagRepository)
    {
    }

    /**
     * @throws Exception
     */
    public function update(array $data, int $id): Flag
    {
        $task = $this->flagRepository->find($id);
        $this->checkPerms($task);
        return $this->flagRepository->update($task, $data);
    }

    /**
     * @throws Exception
     */
    public function destroy(int $id): void
    {
        $task = $this->flagRepository->find($id);
        $this->checkPerms($task);
        $this->flagRepository->destroy($task);
    }

    public function create(array $data): Flag
    {
        $data['user_id'] = auth()->user()->id;
        return $this->flagRepository->create($data);
    }

    private function checkPerms(Flag $flag): void
    {
        if ($flag->owner()->getResults()->id !== auth()->user()->id) {
            throw new Exception('Not allowed');
        }
    }


    public function getAll(): Collection
    {
        /** @var $query Builder */
        $query = Flag::where('user_id', auth()->user()->id);
        return $query->get();
    }
}