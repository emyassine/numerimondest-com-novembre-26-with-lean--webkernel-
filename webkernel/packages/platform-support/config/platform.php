<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Broadcasting
    |--------------------------------------------------------------------------
    |
    | By configuring the Echo-compatible client, you may connect Platform
    | to any Pusher-compatible websockets server.
    |
    | This will allow your users to receive real-time notifications.
    |
    */

    'broadcasting' => [

        // 'echo' => [
        //     'broadcaster' => 'pusher',
        //     'key' => env('VITE_PUSHER_APP_KEY'),
        //     'cluster' => env('VITE_PUSHER_APP_CLUSTER'),
        //     'wsHost' => env('VITE_PUSHER_HOST'),
        //     'wsPort' => env('VITE_PUSHER_PORT'),
        //     'wssPort' => env('VITE_PUSHER_PORT'),
        //     'authEndpoint' => '/broadcasting/auth',
        //     'disableStats' => true,
        //     'encrypted' => true,
        //     'forceTLS' => env('VITE_PUSHER_SCHEME', 'https') === 'https',
        // ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | This is the default disk Platform uses for file operations. Disk
    | definitions come from Webkernel's configured FilesystemManager.
    |
    */

    'default_filesystem_disk' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Temporary File URL Expiry
    |--------------------------------------------------------------------------
    |
    | When Platform generates temporary URLs for previewing private files
    | (file uploads, image columns, image entries, rich editor attachments,
    | etc.), this value controls how many minutes those URLs remain valid.
    |
    | The generated URL's expiry is rounded up to the end of the hour it
    | falls in, so the effective lifetime will be between this value and
    | this value plus up to 60 minutes.
    |
    */

    'temporary_file_url_expiry_minutes' => 30,

    /*
    |--------------------------------------------------------------------------
    | Assets Path
    |--------------------------------------------------------------------------
    |
    | This is the directory where Platform's assets will be published to. It
    | is relative to the application's `public` directory.
    |
    | After changing the path, run `./platform platform:assets`.
    |
    */

    'assets_path' => null,

    /*
    |--------------------------------------------------------------------------
    | Cache Store
    |--------------------------------------------------------------------------
    |
    | This is the cache store Platform will use. You may use any of the stores
    | registered with Webkernel's cache manager.
    |
    | When set to `null`, the application's default cache store will be used.
    |
    */

    'cache_store' => null,

    /*
    |--------------------------------------------------------------------------
    | Cache Path
    |--------------------------------------------------------------------------
    |
    | This is the directory that Platform will use to store cache files that
    | are used to optimize the registration of components.
    |
    | After changing the path, run `./platform platform:cache-components`.
    |
    */

    'cache_path' => base_path('runtime/cache/platform'),

    /*
    |--------------------------------------------------------------------------
    | Liveview Loading Delay
    |--------------------------------------------------------------------------
    |
    | This sets the delay before loading indicators appear.
    |
    | Setting this to 'none' makes indicators appear immediately, which can be
    | desirable for high-latency connections. Setting it to 'default' applies
    | Liveview's standard 200ms delay.
    |
    */

    'liveview_loading_delay' => 'default',

    /*
    |--------------------------------------------------------------------------
    | File Generation
    |--------------------------------------------------------------------------
    |
    | Artisan commands that generate files can be configured here by setting
    | configuration flags that will impact their location or content.
    |
    | Often, this is useful to preserve file generation behavior from a
    | previous version of Platform, to ensure consistency between older and
    | newer generated files. These flags are often documented in the upgrade
    | guide for the version of Platform you are upgrading to.
    |
    */

    'file_generation' => [
        'flags' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | System Route Prefix
    |--------------------------------------------------------------------------
    |
    | This is the prefix used for the system routes that Platform registers,
    | such as the routes for downloading exports and failed import rows.
    |
    */

    'system_route_prefix' => 'platform',

];
