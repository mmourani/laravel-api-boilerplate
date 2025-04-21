<?php

use Illuminate\Support\Str;

return [
    'domain' => env('HORIZON_DOMAIN'),
    'path' => env('HORIZON_PATH', 'horizon'),
    'use' => 'default',
    'prefix' => env(
        'HORIZON_PREFIX',
        Str::slug(env('APP_NAME', 'laravel'), '_').'_horizon:'
    ),
    'middleware' => ['web'],

    'waits' => [
        'redis:default' => 60,
        'redis:emails' => 60,
        'redis:ai-tasks' => 60,
        'redis:critical' => 30,
    ],

    'trim' => [
        'recent' => 60,
        'pending' => 60,
        'completed' => 60,
        'recent_failed' => 10080,
        'failed' => 10080,
        'monitored' => 10080,
    ],

    'silenced' => [],

    'metrics' => [
        'trim_snapshots' => [
            'job' => 24,
            'queue' => 24,
        ],
    ],

    'fast_termination' => false,
    'memory_limit' => 64,

    'defaults' => [
        'supervisor-default' => [
            'connection' => 'redis',
            'queue' => ['default'],
            'balance' => 'auto',
            'autoScalingStrategy' => 'time',
            'maxProcesses' => 1,
            'maxTime' => 0,
            'maxJobs' => 0,
            'memory' => 128,
            'tries' => 1,
            'timeout' => 60,
            'nice' => 0,
        ],
        'supervisor-emails' => [
            'connection' => 'redis',
            'queue' => ['emails'],
            'balance' => 'auto',
            'autoScalingStrategy' => 'time',
            'maxProcesses' => 1,
            'maxTime' => 0,
            'maxJobs' => 0,
            'memory' => 128,
            'tries' => 3,
            'timeout' => 90,
            'nice' => 0,
        ],
        'supervisor-ai' => [
            'connection' => 'redis',
            'queue' => ['ai-tasks'],
            'balance' => 'auto',
            'autoScalingStrategy' => 'time',
            'maxProcesses' => 1,
            'maxTime' => 0,
            'maxJobs' => 0,
            'memory' => 256,
            'tries' => 2,
            'timeout' => 120,
            'nice' => 0,
        ],
        'supervisor-critical' => [
            'connection' => 'redis',
            'queue' => ['critical'],
            'balance' => 'simple',
            'autoScalingStrategy' => 'time',
            'maxProcesses' => 1,
            'maxTime' => 0,
            'maxJobs' => 0,
            'memory' => 128,
            'tries' => 5,
            'timeout' => 60,
            'nice' => -10,
        ],
    ],

    'environments' => [
        'production' => [
            'supervisor-default' => ['maxProcesses' => 10],
            'supervisor-emails' => ['maxProcesses' => 4],
            'supervisor-ai' => ['maxProcesses' => 2],
            'supervisor-critical' => ['maxProcesses' => 3],
        ],
        'local' => [
            'supervisor-default' => ['maxProcesses' => 3],
            'supervisor-emails' => ['maxProcesses' => 2],
            'supervisor-ai' => ['maxProcesses' => 1],
            'supervisor-critical' => ['maxProcesses' => 1],
        ],
    ],
];
