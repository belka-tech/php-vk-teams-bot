<?php

declare(strict_types=1);

namespace BelkaTech\VkTeamsBot\Test\Unit;

use BelkaTech\VkTeamsBot\Bot;
use BelkaTech\VkTeamsBot\BotEventListener;
use BelkaTech\VkTeamsBot\Event\EventDto;
use BelkaTech\VkTeamsBot\Http\HttpClient;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class BotEventListenerTest extends TestCase
{
    public function testListenThrowsOnPollTimeTooLow(): void
    {
        // GIVEN: a listener instance
        $bot = new Bot($this->createMock(HttpClient::class));
        $listener = new BotEventListener($bot);

        // THEN: exception is expected
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('pollTime must be between 1 and 60');

        // WHEN: listen is called with pollTime=0
        $listener->listen(0);
    }

    public function testListenThrowsOnPollTimeTooHigh(): void
    {
        // GIVEN: a listener instance
        $bot = new Bot($this->createMock(HttpClient::class));
        $listener = new BotEventListener($bot);

        // THEN: exception is expected
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('pollTime must be between 1 and 60');

        // WHEN: listen is called with pollTime=61
        $listener->listen(61);
    }

    public function testHandlerRegistration(): void
    {
        // GIVEN: a listener instance
        $bot = new Bot($this->createMock(HttpClient::class));
        $listener = new BotEventListener($bot);

        // WHEN: onMessage handler is registered
        $called = false;
        $listener->onMessage(function () use (&$called) {
            $called = true;
        });

        // THEN: handler is not called immediately
        $this->assertFalse($called);
    }

    public function testCommandRegistration(): void
    {
        // GIVEN: a listener instance
        $bot = new Bot($this->createMock(HttpClient::class));
        $listener = new BotEventListener($bot);

        // WHEN: onCommand handler is registered
        $called = false;
        $listener->onCommand('/start', function () use (&$called) {
            $called = true;
        });

        // THEN: handler is not called immediately
        $this->assertFalse($called);
    }

    public function testDuplicateCommandRegistrationThrows(): void
    {
        // GIVEN: a listener with '/start' command already registered
        $bot = new Bot($this->createMock(HttpClient::class));
        $listener = new BotEventListener($bot);
        $listener->onCommand('/start', function () {});

        // THEN: exception is expected
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Command handler for '/start' is already registered");

        // WHEN: same command is registered again
        $listener->onCommand('/start', function () {});
    }

    public function testCommandMatchDoesNotSkipRemainingEventsInBatch(): void
    {
        // GIVEN: a batch with command event and regular message event
        $httpClient = $this->createMock(HttpClient::class);
        $bot = new Bot($httpClient);
        $listener = new BotEventListener($bot);

        $callCount = 0;
        $this->mockEventsGet($httpClient, $listener, $callCount, [
            'events' => [
                [
                    'eventId' => 1,
                    'type' => 'newMessage',
                    'payload' => ['text' => '/start'],
                ],
                [
                    'eventId' => 2,
                    'type' => 'newMessage',
                    'payload' => ['text' => 'hello'],
                ],
            ],
        ]);

        $commandCalled = false;
        $listener->onCommand('/start', function () use (&$commandCalled) {
            $commandCalled = true;
        });

        $messageCalled = false;
        $listener->onMessage(function () use (&$messageCalled) {
            $messageCalled = true;
        });

        // WHEN: listener processes the batch
        $listener->listen(1);

        // THEN: both handlers are called
        $this->assertTrue($commandCalled, 'Command handler should be called');
        $this->assertTrue($messageCalled, 'onMessage handler should be called for second event in batch');
    }

    public function testOnExceptionCallbackReceivesExceptionAndEvent(): void
    {
        // GIVEN: a handler that throws an exception
        $httpClient = $this->createMock(HttpClient::class);
        $bot = new Bot($httpClient);
        $listener = new BotEventListener($bot);

        $callCount = 0;
        $this->mockEventsGet($httpClient, $listener, $callCount, [
            'events' => [
                [
                    'eventId' => 1,
                    'type' => 'newMessage',
                    'payload' => ['text' => 'hello'],
                ],
            ],
        ]);

        $listener->onMessage(function () {
            throw new \RuntimeException('handler error');
        });

        $caughtException = null;
        $caughtEvent = null;

        // WHEN: listen is called with onException callback
        $listener->listen(
            pollTime: 1,
            onException: function (\Exception $e, EventDto $event) use (&$caughtException, &$caughtEvent) {
                $caughtException = $e;
                $caughtEvent = $event;
            },
        );

        // THEN: onException receives the exception and event
        $this->assertInstanceOf(\RuntimeException::class, $caughtException);
        $this->assertSame('handler error', $caughtException->getMessage());
        $this->assertSame(1, $caughtEvent->eventId);
    }

    public function testLoopContinuesAfterExceptionWithOnExceptionCallback(): void
    {
        // GIVEN: a handler that throws on first event only
        $httpClient = $this->createMock(HttpClient::class);
        $bot = new Bot($httpClient);
        $listener = new BotEventListener($bot);

        $callCount = 0;
        $this->mockEventsGet($httpClient, $listener, $callCount, [
            'events' => [
                [
                    'eventId' => 1,
                    'type' => 'newMessage',
                    'payload' => ['text' => 'first'],
                ],
                [
                    'eventId' => 2,
                    'type' => 'newMessage',
                    'payload' => ['text' => 'second'],
                ],
            ],
        ]);

        $handlerCalls = [];
        $listener->onMessage(function (Bot $bot, EventDto $event) use (&$handlerCalls) {
            $handlerCalls[] = $event->eventId;
            if ($event->eventId === 1) {
                throw new \RuntimeException('fail on first');
            }
        });

        $exceptions = [];

        // WHEN: listen is called with onException callback
        $listener->listen(
            pollTime: 1,
            onException: function (\Exception $e) use (&$exceptions) {
                $exceptions[] = $e->getMessage();
            },
        );

        // THEN: both events are processed, only first triggers onException
        $this->assertSame([1, 2], $handlerCalls, 'Both events should be processed');
        $this->assertSame(['fail on first'], $exceptions, 'Only first event should trigger onException');
    }

    public function testExceptionBubblesWithoutOnExceptionCallback(): void
    {
        // GIVEN: a handler that throws and no onException callback
        $httpClient = $this->createMock(HttpClient::class);
        $bot = new Bot($httpClient);
        $listener = new BotEventListener($bot);

        $callCount = 0;
        $this->mockEventsGet($httpClient, $listener, $callCount, [
            'events' => [
                [
                    'eventId' => 1,
                    'type' => 'newMessage',
                    'payload' => ['text' => 'hello'],
                ],
            ],
        ]);

        $listener->onMessage(function () {
            throw new \RuntimeException('unhandled');
        });

        // THEN: exception bubbles up
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('unhandled');

        // WHEN: listen is called without onException
        $listener->listen(1);
    }

    public function testStopFromHandlerStopsLoop(): void
    {
        // GIVEN: a handler that calls stop() on second event
        $httpClient = $this->createMock(HttpClient::class);
        $bot = new Bot($httpClient);
        $listener = new BotEventListener($bot);

        $pollCount = 0;
        $httpClient
            ->method('get')
            ->willReturnCallback(function () use (&$pollCount, $listener) {
                $pollCount++;
                return [
                    'events' => [
                        [
                            'eventId' => $pollCount,
                            'type' => 'newMessage',
                            'payload' => ['text' => 'msg'],
                        ],
                    ],
                ];
            });

        $listener->onMessage(function (Bot $bot, EventDto $event) use ($listener) {
            if ($event->eventId === 2) {
                $listener->stop();
            }
        });

        // WHEN: listen starts
        $listener->listen(1);

        // THEN: loop stops after 2 polls
        $this->assertSame(2, $pollCount, 'Loop should stop after handler calls stop()');
    }

    public function testUnknownEventTypeIsSkipped(): void
    {
        // GIVEN: a batch with unknown event type followed by known event
        $httpClient = $this->createMock(HttpClient::class);
        $bot = new Bot($httpClient);
        $listener = new BotEventListener($bot);

        $callCount = 0;
        $this->mockEventsGet($httpClient, $listener, $callCount, [
            'events' => [
                [
                    'eventId' => 1,
                    'type' => 'unknownEventType',
                    'payload' => ['text' => 'hello'],
                ],
                [
                    'eventId' => 2,
                    'type' => 'newMessage',
                    'payload' => ['text' => 'world'],
                ],
            ],
        ]);

        $receivedEvents = [];
        $listener->onMessage(function (Bot $bot, EventDto $event) use (&$receivedEvents) {
            $receivedEvents[] = $event->eventId;
        });

        // WHEN: listener processes the batch
        $listener->listen(1);

        // THEN: unknown event is skipped, known event is processed
        $this->assertSame([2], $receivedEvents, 'Unknown event should be skipped, known event processed');
    }

    public function testLastEventIdIsPassedToNextPoll(): void
    {
        // GIVEN: first poll returns event with id=42
        $httpClient = $this->createMock(HttpClient::class);
        $bot = new Bot($httpClient);
        $listener = new BotEventListener($bot);

        $receivedLastEventIds = [];
        $pollCount = 0;
        $httpClient
            ->method('get')
            ->willReturnCallback(function (string $path, array $params) use (&$receivedLastEventIds, &$pollCount, $listener) {
                $receivedLastEventIds[] = $params['lastEventId'];
                $pollCount++;
                if ($pollCount === 1) {
                    return [
                        'events' => [
                            ['eventId' => 42, 'type' => 'newMessage', 'payload' => ['text' => 'hi']],
                        ],
                    ];
                }
                $listener->stop();
                return ['events' => []];
            });

        $listener->onMessage(function () {});

        // WHEN: listen processes two polls
        $listener->listen(1);

        // THEN: second poll uses lastEventId from first batch
        $this->assertSame(0, $receivedLastEventIds[0], 'First poll should use lastEventId=0');
        $this->assertSame(42, $receivedLastEventIds[1], 'Second poll should use lastEventId from previous batch');
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('signalProvider')]
    public function testStopOnSignal(int $signal): void
    {
        if (!function_exists('pcntl_signal')) {
            $this->markTestSkipped('pcntl extension required');
        }

        // GIVEN: a listener that receives a signal on first poll
        $httpClient = $this->createMock(HttpClient::class);
        $bot = new Bot($httpClient);
        $listener = new BotEventListener($bot);

        $pollCount = 0;
        $httpClient
            ->method('get')
            ->willReturnCallback(function () use (&$pollCount, $signal) {
                $pollCount++;
                if ($pollCount === 1) {
                    posix_kill(posix_getpid(), $signal);
                    return [
                        'events' => [
                            ['eventId' => 1, 'type' => 'newMessage', 'payload' => ['text' => 'hi']],
                        ],
                    ];
                }
                $this->fail('Loop should have stopped after signal');
                return ['events' => []];
            });

        $listener->onMessage(function () {});

        // WHEN: listen starts
        $listener->listen(1);

        // THEN: loop stops after signal
        $this->assertSame(1, $pollCount, 'Loop should stop after signal');
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function signalProvider(): iterable
    {
        yield 'SIGTERM' => [SIGTERM];
        yield 'SIGINT' => [SIGINT];
    }

    /**
     * Configure HttpClient mock to return $eventsBatch on first call,
     * then empty events + stop() on subsequent calls.
     */
    private function mockEventsGet(
        MockObject&HttpClient $httpClient,
        BotEventListener $listener,
        int &$callCount,
        array $eventsBatch,
    ): void {
        $httpClient
            ->method('get')
            ->willReturnCallback(function () use (&$callCount, $listener, $eventsBatch) {
                $callCount++;
                if ($callCount === 1) {
                    return $eventsBatch;
                }
                $listener->stop();
                return ['events' => []];
            });
    }
}
