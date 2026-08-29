<?php

namespace App\Providers;

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
        $this->app->bind(ProspectGeneratorInterface::class, StubProspectGenerator::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
