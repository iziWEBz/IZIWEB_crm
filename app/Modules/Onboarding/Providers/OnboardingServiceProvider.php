<?php
namespace App\Modules\Onboarding\Providers;

use Illuminate\Support\ServiceProvider;

class OnboardingServiceProvider extends ServiceProvider
{

    public function register(): void
    {
        // 
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
    }
}
