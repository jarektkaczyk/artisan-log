<?php

use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskStarting;

return [
    /**
     * @see \Psr\Log\LogLevel
     */
    'log_level' => 'info',

    /**
     * Channel where you want logs to be written. You may want to customize it, eg. create a separate log file or
     * send logs to a service of your choice, by specifying customized channel.
     * @see config/logging.php
     */
    'log_channel' => 'default',

    /**
     * Available placeholders:
     *
     * CommandStarting: {command}
     * CommandFinished: {command}, {status} (`finished` OR `failed with exit code: 123`), {exit_code}
     * ScheduledTaskStarting: {command}
     * ScheduledTaskFinished: {command}, {runtime} (runtime in seconds)
     */
    'formats' => [
        CommandStarting::class => '[artisan starting] {command}', // placeholders: {command}
        CommandFinished::class => '[artisan {status}] {command}', // placeholders: {
        ScheduledTaskStarting::class => '[artisan scheduled starting] {command}',
        ScheduledTaskFinished::class => '[artisan scheduled finished] {command}',
    ],

    /**
     * Below example of customized channel & formats writing to a separate log file:
     *
     * config/logging.php:
     * ```
     * 'channels' => [
     *   ...
     *   'artisan' => [
     *     'driver' => 'daily',
     *     'path' => storage_path('logs/artisan.log'),
     *     'days' => 7,
     *   ],
     * ],
     * ```
     */

//    'log_channel' => 'artisan',
//    'formats' => [
//        CommandStarting::class => '[starting] {command}',
//        CommandFinished::class => '[{status}] {command}',
//        ScheduledTaskStarting::class => '[scheduled starting] {command}',
//        ScheduledTaskFinished::class => '[scheduled finished] {command}',
//    ],


    /**
     * Select which artisan commands you don't want to be logged.
     * For example it doesn't make much sense to log just the `artisan` command, `artisan schedule:run`
     * which is executed every minute or `artisan make:SOMETHING`.
     */
    'ignored' => [
        '',
        'test',
        'list',
        'help',
        'env',
        'make:*',
        'route:list',
        'event:list',
        'schedule:run',
        'migrate:status',
        'ide-helper:*',
    ],

    /**
     * You may want to use custom logger implementation. Provide its classname here, it will be resolved by IoC.
     * @see \Psr\Log\LoggerInterface
     */
    'custom_logger' => null,
];
