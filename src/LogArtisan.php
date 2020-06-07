<?php

namespace Sofa\ArtisanLog;

use Closure;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskStarting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Throwable;

class LogArtisan
{
    const DEFAULT_LEVEL = LogLevel::INFO;
    const DEFAULT_FORMATS = [
        CommandStarting::class => '[starting] {command}',
        CommandFinished::class => '[{status}] {command}',
        ScheduledTaskStarting::class => '[scheduled starting] {command}',
        ScheduledTaskFinished::class => '[scheduled finished] {command}',
    ];

    protected LoggerInterface $logger;
    protected string $logLevel;

    /** @var array|string[] Log formats for each logged event */
    protected array $formats;

    /** @var Collection|string[] Currently logged commands, so we avoid duplicates for scheduled tasks */
    protected Collection $current;

    /** @var Collection|string[] Commands to be ignored */
    protected Collection $ignored;

    /** @var Collection|string[] Command wildcards to be ignored */
    protected Collection $ignoredWildcards;

    public function __construct(
        LoggerInterface $logger,
        string $logLevel = self::DEFAULT_LEVEL,
        array $formats = [],
        array $ignored = []
    ) {
        $this->logger = $logger;
        $this->logLevel = $logLevel;
        $this->formats = $formats + self::DEFAULT_FORMATS;

        $this->current = new Collection;
        $this->setIgnored($ignored);
    }

    public static function subscribe()
    {
        Event::listen(
            [
                CommandStarting::class,
                CommandFinished::class,
                ScheduledTaskStarting::class,
                ScheduledTaskFinished::class,
            ],
            self::class
        );
    }

    /**
     * @param CommandStarting|CommandFinished|ScheduledTaskStarting|ScheduledTaskFinished $event
     */
    public function handle($event): void
    {
        try {
            $command = $this->parseName($event);
            if ($this->shouldIgnore($command, $event)) {
                return;
            }

            $this->current[] = $command;
            $replacements = Collection::make(['command' => $command] + $this->getReplacements($event))
                ->mapWithKeys(fn ($value, $placeholder) => ['{' . $placeholder . '}' => $value])
                ->toArray();

            $this->logger->log(
                $this->logLevel,
                str_replace(array_keys($replacements), $replacements, $this->formats[get_class($event)])
            );
        } catch (Throwable $e) {
            // We don't want logger to break the command execution, so we'll just report exception and keep going
            function_exists('report') ? report($e) : $this->logger->error($e);
        }
    }

    /**
     * @param CommandStarting|CommandFinished|ScheduledTaskStarting|ScheduledTaskFinished $event
     * @return array
     */
    protected function getReplacements($event): array
    {
        if ($event instanceof CommandFinished) {
            return [
                'status' => $event->exitCode === 0 ? 'finished' : 'failed with exit code: ' . $event->exitCode,
                'exit_code' => $event->exitCode,
            ];
        }

        if ($event instanceof ScheduledTaskFinished) {
            return [
                'runtime' => $event->runtime . 's',
            ];
        }

        return [];
    }

    public function ignore(?string $command): self
    {
        Str::endsWith($command, '*')
            ? $this->ignoredWildcards[] = trim($command, '*')
            : $this->ignored[] = $command;

        return $this;
    }

    public function setIgnored(array $commands): self
    {
        $this->ignored = new Collection();
        $this->ignoredWildcards = new Collection;

        foreach ($commands as $command) {
            $this->ignore($command);
        }

        return $this;
    }

    protected function shouldIgnore(string $command, $event): bool
    {
        return $this->ignored->contains($command)
            || $this->current->contains($command) && $event instanceof CommandStarting
            || $this->ignoredWildcards->contains(fn ($wildcard) => Str::startsWith($command, $wildcard));
    }

    private function parseName($event): string
    {
        static $closures;
        $closures ??= 0;

        $name = $event instanceof CommandStarting || $event instanceof CommandFinished
            ? $this->commandName($event)
            : $this->scheduleName($event);

        if ($name === 'closure command') {
            $name .= (++$closures);
        }

        return $name;
    }

    /**
     * @param CommandStarting|CommandFinished $event
     * @return string
     */
    private function commandName($event): string
    {
        if ($event->command instanceof Closure) {
            return 'closure command';
        }

        try {
            $command = (string)$event->input;

            // This is to output `some-command args` rather than `'some-command' args`
            return str_replace("'{$event->command}'", $event->command, $command);
        } catch (Throwable $e) {
            return $event->command;
        }
    }

    /**
     * @param ScheduledTaskStarting|ScheduledTaskFinished $event
     * @return string
     */
    private function scheduleName($event)
    {
        if ($event->task->command instanceof Closure) {
            return 'closure command';
        }

        // We're likely to deal with and artisan command - we'll strip irrelevant parts if that's the case
        if (Str::contains($event->task->command, 'artisan')) {
            return Collection::make(preg_split('/\h+/', $event->task->command, -1, PREG_SPLIT_NO_EMPTY))
                ->slice(1)
                ->filter(fn ($part) => !in_array($part, ['artisan', "'artisan'"]))
                ->implode(' ');
        }

        return $event->task->command;
    }
}
