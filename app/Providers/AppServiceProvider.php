<?php

namespace App\Providers;

use App\Services\EmailTemplateGenerator\EmailTemplateGeneratorInterface;
use App\Services\EmailTemplateGenerator\OpenAiEmailTemplateGenerator;
use App\Services\EmailTemplateGenerator\StubEmailTemplateGenerator;
use App\Services\MailSender\LogMailSender;
use App\Services\MailSender\MailjetMailSender;
use App\Services\MailSender\MailSenderInterface;
use App\Services\ProfileSearch\BraveProfileSearchService;
use App\Services\ProfileSearch\ProfileSearchInterface;
use App\Services\ProfileSearch\StubProfileSearchService;
use App\Services\ProspectGenerator\OpenAiProspectGenerator;
use App\Services\ProspectGenerator\ProspectGeneratorInterface;
use App\Services\ProspectGenerator\StubProspectGenerator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ProspectGeneratorInterface::class, function () {
            // Keep tests hermetic (no network calls) regardless of whether a key is configured.
            if ($this->app->environment('testing') || empty(config('services.openai.key'))) {
                return new StubProspectGenerator();
            }

            return new OpenAiProspectGenerator(
                (string) config('services.openai.key'),
                (string) config('services.openai.model'),
            );
        });

        $this->app->bind(EmailTemplateGeneratorInterface::class, function () {
            if ($this->app->environment('testing') || empty(config('services.openai.key'))) {
                return new StubEmailTemplateGenerator();
            }

            return new OpenAiEmailTemplateGenerator(
                (string) config('services.openai.key'),
                (string) config('services.openai.model'),
            );
        });

        $this->app->bind(ProfileSearchInterface::class, function () {
            // Keep tests hermetic (no network calls) regardless of whether a key is configured.
            if ($this->app->environment('testing') || empty(config('services.brave_search.key'))) {
                return new StubProfileSearchService();
            }

            return new BraveProfileSearchService((string) config('services.brave_search.key'));
        });

        $this->app->bind(MailSenderInterface::class, function () {
            // Keep tests hermetic (no network calls) regardless of whether keys are configured.
            if ($this->app->environment('testing')
                || empty(config('services.mailjet.key'))
                || empty(config('services.mailjet.secret'))
            ) {
                return new LogMailSender();
            }

            return new MailjetMailSender(
                (string) config('services.mailjet.key'),
                (string) config('services.mailjet.secret'),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
