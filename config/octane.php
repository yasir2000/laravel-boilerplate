<?php

// use Laravel\Octane\Facades\Octane;
// use Laravel\Octane\Listeners\EnsureUploadedFilesAreValid;
// use Laravel\Octane\Listeners\EnsureUploadedFilesCanBeMoved;
// use Laravel\Octane\Listeners\FlushTemporaryFilesystem;
// use Laravel\Octane\Listeners\ReportException;
// use Laravel\Octane\Listeners\StopWorkerIfNecessary;
// use Laravel\Octane\Events\RequestReceived;
// use Laravel\Octane\Events\RequestHandled;
// use Laravel\Octane\Events\RequestTerminated;
// use Laravel\Octane\Events\TaskReceived;
// use Laravel\Octane\Events\TaskTerminated;
// use Laravel\Octane\Events\TickReceived;
// use Laravel\Octane\Events\TickTerminated;
// use Laravel\Octane\Events\WorkerErrorOccurred;
// use Laravel\Octane\Events\WorkerStarting;
// use Laravel\Octane\Events\WorkerStopping;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Server
    |--------------------------------------------------------------------------
    |
    | This option controls the default "server" that will be used by Octane
    | when starting, restarting, or stopping via the command line. You are
    | free to set this to any of the servers supported by the application.
    |
    */

    'server' => env('OCTANE_SERVER', 'frankenphp'),

    /*
    |--------------------------------------------------------------------------
    | FrankenPHP Server Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may specify the configuration options that should be used when
    | Octane is running on top of FrankenPHP. FrankenPHP is a modern PHP
    | application server written in Go that provides excellent performance.
    |
    */

    'frankenphp' => [
        'host' => env('OCTANE_HOST', '127.0.0.1'),
        'port' => env('OCTANE_PORT', 8000),
        'workers' => env('OCTANE_WORKERS', 'auto'),
        'max_requests' => env('OCTANE_MAX_REQUESTS', 500),
        'https' => env('OCTANE_HTTPS', false),
        'http_redirect' => env('OCTANE_HTTP_REDIRECT', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Swoole Server Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may specify the configuration options that should be used when
    | Octane is running on top of the Swoole server. Most of these options
    | are only utilized by the "serve" command when starting the server.
    |
    */

    'swoole' => [
        'host' => env('OCTANE_HOST', '127.0.0.1'),
        'port' => env('OCTANE_PORT', 8000),
        'workers' => env('OCTANE_WORKERS', 'auto'),
        'task_workers' => env('OCTANE_TASK_WORKERS', 'auto'),
        'max_requests' => env('OCTANE_MAX_REQUESTS', 500),
        'tick_interval' => env('OCTANE_TICK_INTERVAL', 1000),
        'options' => [
            'log_file' => storage_path('logs/swoole_http.log'),
            'log_level' => env('SWOOLE_LOG_LEVEL', 0),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | RoadRunner Server Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may specify the configuration options that should be used when
    | Octane is running on top of the RoadRunner server. Most of these options
    | are only utilized by the "serve" command when starting the server.
    |
    */

    'roadrunner' => [
        'host' => env('OCTANE_HOST', '127.0.0.1'),
        'port' => env('OCTANE_PORT', 8000),
        'rpc_host' => env('OCTANE_RPC_HOST', '127.0.0.1'),
        'rpc_port' => env('OCTANE_RPC_PORT', 6001),
        'workers' => env('OCTANE_WORKERS', 'auto'),
        'max_requests' => env('OCTANE_MAX_REQUESTS', 500),
    ],

    /*
    |--------------------------------------------------------------------------
    | Warm / Flush Types
    |--------------------------------------------------------------------------
    |
    | This option controls which of your application's "types" (classes) will
    | be pre-loaded into Octane's worker processes so they do not need to be
    | loaded again. This can improve your application's total performance.
    |
    */

    'warm' => [
        'config',
        'views',
        'blade-icons',
        'blade-icon-families',
        'blade-icon-sets',
    ],

    /*
    |--------------------------------------------------------------------------
    | Octane Cache Table
    |--------------------------------------------------------------------------
    |
    | While using Swoole, you may leverage the Octane cache, which is powered
    | by a Swoole table. You may set the maximum number of rows as well as
    | the number of bytes per row using the configuration options below.
    |
    */

    'cache' => [
        'rows' => 1000,
        'bytes' => 10000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Octane Listeners
    |--------------------------------------------------------------------------
    |
    | All of the event listeners for Octane's events are defined below. These
    | listeners are responsible for resetting your application's state so
    | requests are handled properly. You may even add your own listeners.
    |
    */

    'listeners' => [
        // WorkerStarting::class => [
        //     EnsureUploadedFilesAreValid::class,
        //     EnsureUploadedFilesCanBeMoved::class,
        // ],

        // RequestReceived::class => [
        //     // ...Octane::prepareApplicationForNextOperation(),
        //     // ...Octane::prepareApplicationForNextRequest(),
        //     //
        // ],

        // RequestHandled::class => [
        //     //
        // ],

        // RequestTerminated::class => [
        //     FlushTemporaryFilesystem::class,
        // ],

        // TaskReceived::class => [
        //     // ...Octane::prepareApplicationForNextOperation(),
        //     //
        // ],

        // TaskTerminated::class => [
        //     //
        // ],

        // TickReceived::class => [
        //     // ...Octane::prepareApplicationForNextOperation(),
        //     //
        // ],

        // TickTerminated::class => [
        //     //
        // ],

        // WorkerErrorOccurred::class => [
        //     ReportException::class,
        //     StopWorkerIfNecessary::class,
        // ],

        // WorkerStopping::class => [
        //     //
        // ],
    ],
];