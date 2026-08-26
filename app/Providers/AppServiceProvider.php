<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

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
        RateLimiter::for('registration', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('login', function (Request $request) {
            $email = Str::lower((string) $request->input('email'));

            return [
                Limit::perMinute(20)->by($request->ip()),
                Limit::perMinute(5)->by($email.'|'.$request->ip()),
            ];
        });

        RateLimiter::for('verification-resend', function (Request $request) {
            $email = Str::lower((string) $request->input('email'));

            return [
                Limit::perMinutes(10, 10)->by($request->ip()),
                Limit::perMinutes(10, 3)->by($email.'|'.$request->ip()),
            ];
        });

        RateLimiter::for('password-recovery', function (Request $request) {
            $email = Str::lower((string) $request->input('email'));

            return [
                Limit::perMinutes(10, 10)->by($request->ip()),
                Limit::perMinutes(10, 3)->by($email.'|'.$request->ip()),
            ];
        });

        RateLimiter::for('password-reset', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('project-messages', function (Request $request) {
            $project = $request->route('project');
            $projectId = is_object($project) ? $project->id : $project;

            return Limit::perMinute(30)->by($request->user()->id.'|'.$projectId);
        });

        RateLimiter::for('project-reactions', function (Request $request) {
            $message = $request->route('message');
            $projectId = is_object($message) ? $message->project_id : 'message-'.$message;

            return Limit::perMinute(60)->by($request->user()->id.'|'.$projectId);
        });

        $driver = DB::getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement("SET time_zone='+08:00'");
        }
    }
}
