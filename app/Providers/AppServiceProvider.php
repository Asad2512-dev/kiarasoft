<?php

namespace App\Providers;

use App\Models\User;
use App\Services\AlertService;
use App\Support\Money;
use App\Support\Permissions;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Paginator::useBootstrapFive();

        // Every permission in the central map becomes a Gate ability (usable as can:xyz middleware / @can).
        foreach (array_unique(array_merge(...array_values(Permissions::MAP))) as $permission) {
            Gate::define($permission, fn (User $user) => $user->is_active && $user->hasPermission($permission));
        }

        RateLimiter::for('login', fn (Request $r) => [
            Limit::perMinute(5)->by(strtolower((string) $r->input('email')).'|'.$r->ip()),
            Limit::perMinute(20)->by($r->ip()),
        ]);
        RateLimiter::for('checkout', fn (Request $r) => Limit::perMinute(60)->by($r->user()?->id ?: $r->ip()));
        RateLimiter::for('public-forms', fn (Request $r) => Limit::perHour(10)->by($r->ip()));

        Blade::directive('money', fn ($e) => "<?php echo e(\\App\\Support\\Money::format({$e})); ?>");
        Blade::directive('qty', fn ($e) => "<?php echo e(\\App\\Support\\Money::qty({$e})); ?>");

        View::composer('layouts.app', function ($view) {
            $user = auth()->user();
            $view->with('alertCounts', $user && $user->shop_id ? app(AlertService::class)->counts($user) : ['low' => 0, 'out' => 0, 'total' => 0]);
        });
    }
}
