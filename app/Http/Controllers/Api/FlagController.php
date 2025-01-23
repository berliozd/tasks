<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Flag;
use App\Services\FlagService;
use Exception;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;

class FlagController extends Controller
{
    public function __construct(private readonly FlagService $flagService)
    {
    }

    public function index()
    {
        return $this->flagService->getAll();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): ?Flag
    {
        try {
            return $this->flagService->create($request->toArray());
        } catch (UniqueConstraintViolationException $e) {
            throw new Exception('A flag with that color code already exists');
        }
    }

    /**
     * Update the specified resource in storage.
     * @throws Exception
     */
    public function update(Request $request, string $id)
    {
        return $this->flagService->update($request->toArray(), (int)$id);
    }

    /**
     * Remove the specified resource from storage.
     * @throws Exception
     */
    public function destroy(string $id)
    {
        $this->flagService->destroy((int)$id);
    }
}
