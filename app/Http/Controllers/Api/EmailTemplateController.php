<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\EmailTemplateService;
use Exception;
use Illuminate\Http\Request;

class EmailTemplateController extends Controller
{
    public function __construct(private readonly EmailTemplateService $emailTemplateService)
    {
    }

    /**
     * @throws Exception
     */
    public function index(string $directoryId)
    {
        return $this->emailTemplateService->getList((int) $directoryId);
    }

    /**
     * @throws Exception
     */
    public function store(Request $request, string $directoryId)
    {
        return $this->emailTemplateService->create([
            ...$request->toArray(),
            'directory_id' => (int) $directoryId,
        ]);
    }

    /**
     * @throws Exception
     */
    public function update(Request $request, string $id)
    {
        return $this->emailTemplateService->update($request->toArray(), (int) $id);
    }

    /**
     * @throws Exception
     */
    public function destroy(string $id)
    {
        $this->emailTemplateService->destroy((int) $id);
    }

    /**
     * @throws Exception
     */
    public function generate(Request $request, string $directoryId)
    {
        return $this->emailTemplateService->generate((int) $directoryId, (string) $request->input('prompt', ''));
    }
}
