<?php

namespace App\Modules\IAM\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class IAMServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Route::prefix('api/v1')
            ->middleware('api')
            ->group(__DIR__ . '/../Routes/api.php');
    }
}
