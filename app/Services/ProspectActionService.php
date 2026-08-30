<?php

namespace App\Services;

use App\Models\Directory;
use App\Models\Prospect;
use App\Models\ProspectAction;
use App\Repositories\DirectoryRepository;
use App\Repositories\EmailTemplateRepository;
use App\Repositories\ProspectActionRepository;
use App\Repositories\ProspectRepository;
use App\Services\MailSender\MailSenderInterface;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;

readonly class ProspectActionService
{
    private const TYPES = ['email', 'call', 'linkedin', 'meeting', 'other'];

    public function __construct(
        private ProspectActionRepository $prospectActionRepository,
        private ProspectRepository $prospectRepository,
        private DirectoryRepository $directoryRepository,
        private EmailTemplateRepository $emailTemplateRepository,
        private MailSenderInterface $mailSender,
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
        $status = $data['status'] ?? 'pending';
        $this->validateType($type);
        $this->validateStatus($status);

        $subject = $data['subject'] ?? null;
        $fromLabel = $data['from_label'] ?? null;
        $replyToEmail = $data['reply_to_email'] ?? null;
        $message = $data['message'] ?? null;

        return $this->prospectActionRepository->create([
            'prospect_id' => $prospect->id,
            'email_template_id' => $this->resolveEmailTemplateId($data['email_template_id'] ?? null, $prospect),
            'type' => $type,
            'subject' => $subject,
            'from_label' => $fromLabel,
            'reply_to_email' => $replyToEmail,
            'message' => $message,
            'status' => $status,
            'queued_for_send' => !empty($data['queued_for_send']),
            'scheduled_at' => !empty($data['scheduled_at']) ? Carbon::parse($data['scheduled_at']) : now(),
        ]);
    }

    /**
     * Send an already-logged, still-pending email action now.
     *
     * From email resolves as: explicit override, then the platform's own
     * sending address (emails always send from the platform mailbox —
     * actions/directories/products don't own a from address). The from
     * label and reply-to resolve as: explicit override (reply-to only),
     * then the value stored on the action, then the directory's own
     * setting, then the owning product's setting as a fallback default.
     * Only whichever from label/reply-to actually get used are persisted
     * back onto the action, so the log reflects what really went out.
     *
     * @throws Exception
     */
    public function send(int $id, ?string $fromEmail = null, ?string $replyToEmail = null): ProspectAction
    {
        $action = $this->findAction($id);
        $this->checkPerms($action);

        return $this->sendAction($action, $fromEmail, $replyToEmail);
    }

    /**
     * Same as send(), but skips the auth()-based ownership check — for use
     * by unattended console/scheduled runs where there is no logged-in user.
     * Never expose this over HTTP.
     *
     * @throws Exception
     */
    public function sendAsSystem(int $id): ProspectAction
    {
        return $this->sendAction($this->findAction($id));
    }

    /**
     * @throws Exception
     */
    private function sendAction(ProspectAction $action, ?string $fromEmail = null, ?string $replyToEmail = null): ProspectAction
    {
        if ($action->type !== 'email') {
            throw new Exception('Only email actions can be sent');
        }
        if ($action->status !== 'pending') {
            throw new Exception('Only pending actions can be sent');
        }

        $prospect = $action->prospect ?? $this->findProspect($action->prospect_id);
        if (empty($prospect->email)) {
            throw new Exception('This prospect has no email address');
        }

        $directory = $prospect->directory ?? $this->directoryRepository->find($prospect->directory_id);
        $product = $directory?->product;

        $resolvedFromEmail = $fromEmail ?: (string) config('services.prospection.from_email');
        $resolvedFromLabel = $action->from_label ?: $directory?->from_label ?: $product?->from_label;
        $resolvedReplyTo = $replyToEmail ?: $action->reply_to_email
            ?: $directory?->default_reply_to_email ?: $product?->default_reply_to_email;

        if (empty($resolvedFromEmail)) {
            throw new Exception('A from email is required to send (set one on the action, or configure PROSPECTION_FROM_EMAIL)');
        }

        $this->mailSender->send(
            $prospect->email,
            $prospect->name,
            $resolvedFromEmail,
            $resolvedFromLabel,
            (string) $action->subject,
            (string) $action->message,
            $resolvedReplyTo,
        );

        return $this->prospectActionRepository->update($action, [
            'status' => 'sent',
            'queued_for_send' => false,
            'from_label' => $resolvedFromLabel,
            'reply_to_email' => $resolvedReplyTo,
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
        if (array_key_exists('subject', $data)) {
            $update['subject'] = $data['subject'];
        }
        if (array_key_exists('from_label', $data)) {
            $update['from_label'] = $data['from_label'];
        }
        if (array_key_exists('reply_to_email', $data)) {
            $update['reply_to_email'] = $data['reply_to_email'];
        }
        if (array_key_exists('queued_for_send', $data)) {
            $update['queued_for_send'] = (bool) $data['queued_for_send'];
        }
        if (array_key_exists('scheduled_at', $data)) {
            $update['scheduled_at'] = !empty($data['scheduled_at']) ? Carbon::parse($data['scheduled_at']) : null;
        }
        if (array_key_exists('email_template_id', $data)) {
            $prospect = $action->prospect ?? $this->findProspect($action->prospect_id);
            $update['email_template_id'] = $this->resolveEmailTemplateId($data['email_template_id'], $prospect);
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
    private function resolveEmailTemplateId(mixed $emailTemplateId, Prospect $prospect): ?int
    {
        if (empty($emailTemplateId)) {
            return null;
        }

        $template = $this->emailTemplateRepository->find((int) $emailTemplateId);
        if (!$template || (int) $template->directory_id !== (int) $prospect->directory_id) {
            throw new Exception('Email template does not belong to this prospect\'s directory');
        }

        return $template->id;
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
        if (!in_array($status, ProspectAction::STATUSES, true)) {
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
