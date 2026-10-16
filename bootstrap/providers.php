<?php

use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    App\Modules\IAM\Providers\IAMServiceProvider::class,
    App\Modules\Onboarding\Providers\OnboardingServiceProvider::class,
    App\Modules\ClientEnvironment\Providers\ClientEnvironmentServiceProvider::class,
    App\Modules\Management\Providers\ManagementServiceProvider::class,
];
