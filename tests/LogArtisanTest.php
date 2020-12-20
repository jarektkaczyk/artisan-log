<?php

namespace Sofa\ArtisanLog\Tests;

use Exception;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskStarting;
use Illuminate\Console\Scheduling\CallbackEvent;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\EventMutex;
use Illuminate\Support\Facades\Event as EventFacade;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Sofa\ArtisanLog\LogArtisan;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LogArtisanTest extends MockeryTestCase
{
    public function testStaticSubscribe()
    {
        EventFacade::shouldReceive('listen')->once()->with(
            [
                CommandStarting::class,
                CommandFinished::class,
                ScheduledTaskStarting::class,
                ScheduledTaskFinished::class,
            ],
            LogArtisan::class,
        );

        LogArtisan::subscribe();
    }

    public function testHandleDoesntBreakExecution()
    {
        $event = new CommandStarting(
            'cool:command arg1 --opt1',
            $this->createMock(InputInterface::class),
            $this->createMock(OutputInterface::class)
        );
        $logger = $this->createMock(LoggerInterface::class);
        $logger->method('log')->willThrowException($e = new Exception());

        $logger->expects($this->once())->method('error')->with($e);

        $handler = new LogArtisan($logger);
        $handler->handle($event);
    }

    public function testHandlePrintsHumandFriendlyCommand()
    {
        $input = $this->createMock(ArgvInput::class);
        $input->method('__toString')->willReturn("'cool:command' arg1 --opt1");
        $event = new CommandStarting(
            'cool:command',
            $input,
            $this->createMock(OutputInterface::class)
        );

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('log')
            ->with(LogLevel::INFO, '[starting] cool:command arg1 --opt1');

        $handler = new LogArtisan($logger);
        $handler->handle($event);
    }

    /**
     * @dataProvider events
     * @param $event
     * @param string|null $line
     */
    public function testHandle($event, ?string $line)
    {
        $logger = $this->createMock(LoggerInterface::class);
        $formats = [
            CommandStarting::class => '[artisan starting] {command}',
            CommandFinished::class => '[artisan {status}] {command}',
            ScheduledTaskStarting::class => '[scheduled artisan starting] {command}',
            ScheduledTaskFinished::class => '[scheduled artisan finished in {runtime}] {command}',
        ];
        $handler = new LogArtisan($logger, LogLevel::DEBUG, $formats);
        $handler->ignore('dont-log-me');
        $handler->ignore('make:*');

        $logger
            ->expects($line === null ? $this->never() : $this->once())
            ->method('log')
            ->with(LogLevel::DEBUG, $line);

        $handler->handle($event);
    }

    public function events()
    {
        $input = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);
        $scheduledCommand = new Event(
            $this->createMock(EventMutex::class),
            "'/usr/bin/php' 'artisan' scheduled:command arg1 --opt1"
        );
        $scheduledCallable = new CallbackEvent(
            $this->createMock(EventMutex::class),
            fn () => null
        );

        return [
            [
                new CommandStarting('cool:command arg1 --opt1', $input, $output),
                '[artisan starting] cool:command arg1 --opt1',
            ],
            [
                new CommandFinished('cool:command arg1 --opt1', $input, $output, 0),
                '[artisan finished] cool:command arg1 --opt1',
            ],
            [
                new CommandFinished('cool:command arg1 --opt1', $input, $output, 123),
                '[artisan failed with exit code: 123] cool:command arg1 --opt1',
            ],
            [
                new ScheduledTaskStarting($scheduledCommand),
                '[scheduled artisan starting] scheduled:command arg1 --opt1',
            ],
            [
                new ScheduledTaskFinished($scheduledCommand, 1.23),
                '[scheduled artisan finished in 1.23s] scheduled:command arg1 --opt1',
            ],
            [
                new CommandStarting('dont-log-me', $input, $output),
                null
            ],
            [
                new CommandStarting('make:controller', $input, $output),
                null
            ],
            [
                new ScheduledTaskStarting($scheduledCallable),
                '[scheduled artisan starting] closure command1',
            ],
        ];
    }
}
