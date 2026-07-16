<?php

namespace App\Providers;

use App\Models\AppleCatch;
use App\Models\Variety;
use App\Policies\CatchPolicy;
use App\Policies\VarietyPolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Variety::class, VarietyPolicy::class);
        Gate::policy(AppleCatch::class, CatchPolicy::class);

        Model::preventLazyLoading(! app()->isProduction());
    }
}
