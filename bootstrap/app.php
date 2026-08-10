<?php

use App\Http\Middleware\EnsureProjectEditor;
use App\Http\Middleware\EnsureProjectMember;
use App\Http\Middleware\EnsureProjectViewer;
use App\Http\Middleware\IsProjectOwner;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\HandleCors;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(HandleCors::class);

        // Pass everything as a single associative array
        $middleware->alias([
            'project.member' => EnsureProjectMember::class,
            'project.viewer' => EnsureProjectViewer::class,
            'project.editor' => EnsureProjectEditor::class,
            'project.owner'  => IsProjectOwner::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
