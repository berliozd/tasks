<?php

namespace App\Services;

use App\Models\Directory;
use App\Models\EmailTemplate;
use App\Repositories\DirectoryRepository;
use App\Repositories\EmailTemplateRepository;
use App\Services\EmailTemplateGenerator\EmailTemplateGeneratorInterface;
use Exception;
use Illuminate\Support\Collection;

readonly class EmailTemplateService
{
    public function __construct(
        private EmailTemplateRepository $emailTemplateRepository,
        private DirectoryRepository $directoryRepository,
        private EmailTemplateGeneratorInterface $emailTemplateGenerator,
    ) {
    }

    /**
     * @throws Exception
     */
    public function getList(int $directoryId): Collection
    {
        $this->checkDirectoryPerms($this->findDirectory($directoryId));
        return $this->emailTemplateRepository->getList($directoryId);
    }

    /**
     * @throws Exception
     */
    public function find(int $id): EmailTemplate
    {
        $template = $this->findTemplate($id);
        $this->checkPerms($template);
        return $template;
    }

    /**
     * @throws Exception
     */
    public function create(array $data): EmailTemplate
    {
        $directory = $this->findDirectory((int) ($data['directory_id'] ?? 0));
        $this->checkDirectoryPerms($directory);

        return $this->emailTemplateRepository->create([
            'directory_id' => $directory->id,
            'name' => $data['name'] ?? 'Untitled template',
            'subject' => $data['subject'] ?? null,
            'language' => $this->resolveLanguage($data['language'] ?? null),
            'body' => $data['body'] ?? '',
        ]);
    }

    /**
     * @throws Exception
     */
    public function update(array $data, int $id): EmailTemplate
    {
        $template = $this->findTemplate($id);
        $this->checkPerms($template);
        return $this->emailTemplateRepository->update($template, [
            'name' => $data['name'] ?? $template->name,
            'subject' => $data['subject'] ?? null,
            // Language is fixed at creation time — not editable afterwards.
            'language' => $template->language,
            'body' => $data['body'] ?? $template->body,
        ]);
    }

    /**
     * @throws Exception
     */
    public function destroy(int $id): void
    {
        $template = $this->findTemplate($id);
        $this->checkPerms($template);
        $this->emailTemplateRepository->destroy($template);
    }

    /**
     * @throws Exception
     */
    public function generate(int $directoryId, string $prompt, ?string $language = null): EmailTemplate
    {
        $directory = $this->findDirectory($directoryId);
        $this->checkDirectoryPerms($directory);

        if (trim($prompt) === '') {
            throw new Exception('A prompt is required to generate a template');
        }

        $language = $this->resolveLanguage($language);
        $product = $directory->product;
        $row = $this->emailTemplateGenerator->generate(
            $prompt,
            $directory->prompt,
            $product ? ['name' => $product->name, 'website_url' => $product->website_url, 'brief' => $product->brief] : null,
            EmailTemplate::LANGUAGES[$language]
        );

        return $this->emailTemplateRepository->create([
            'directory_id' => $directory->id,
            'name' => $row['name'],
            'subject' => $row['subject'] ?? null,
            'language' => $language,
            'body' => $row['body'],
        ]);
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
    private function findTemplate(int $id): EmailTemplate
    {
        $template = $this->emailTemplateRepository->find($id);
        if (!$template) {
            throw new Exception('Email template not found');
        }
        return $template;
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
    private function checkPerms(EmailTemplate $template): void
    {
        $directory = $template->directory ?? $this->findDirectory($template->directory_id);
        $this->checkDirectoryPerms($directory);
    }

    /**
     * @throws Exception
     */
    private function resolveLanguage(?string $language): string
    {
        $language = $language ?: 'en';
        if (!array_key_exists($language, EmailTemplate::LANGUAGES)) {
            throw new Exception('Invalid language');
        }
        return $language;
    }
}
