<?php

namespace App\Repositories;

use App\Models\EmailTemplate;
use Illuminate\Support\Collection;

readonly class EmailTemplateRepository
{
    public function create(array $data): EmailTemplate
    {
        return EmailTemplate::create($data);
    }

    public function find(int $id): ?EmailTemplate
    {
        return EmailTemplate::find($id);
    }

    public function getList(int $directoryId): Collection
    {
        return EmailTemplate::where('directory_id', $directoryId)->orderByDesc('created_at')->get();
    }

    public function update(EmailTemplate $template, array $data): EmailTemplate
    {
        $template->fill($data);
        $template->save();
        return $template;
    }

    public function destroy(EmailTemplate $template): void
    {
        $template->delete();
    }
}
