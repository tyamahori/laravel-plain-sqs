<?php

use App\Jobs\HandlerJob;

/**
 * List of plain SQS queues and their corresponding handling classes
 */
return [
    'handlers' => [
        'base-integrations-updates' => HandlerJob::class,
    ],

    'default-handler' => HandlerJob::class
];
