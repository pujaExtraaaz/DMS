<?php

return [

    /*
    | Comma-separated proxy addresses, or "*". Empty leaves Laravel's default.
    | Set this when the app sits behind a load balancer so HTTPS and client IP are correct.
    */
    'trusted_proxies' => env('TRUSTED_PROXIES'),

    /*
    | Force generated URLs to https. Leave false on a plain HTTP server.
    */
    'force_https' => (bool) env('APP_FORCE_HTTPS', false),

    /*
    | Disk name from config/filesystems.php. Backups record this name on the row,
    | so an existing backup still restores from the disk it was written to.
    */
    'backup_disk' => env('BACKUP_DISK', 'local'),

    /*
    | When true, the first webhook attempt is queued. Retries stay on webhooks:deliver.
    | Leave false unless a queue worker is running.
    */
    'queue_webhooks' => (bool) env('WEBHOOK_QUEUE', false),

];
