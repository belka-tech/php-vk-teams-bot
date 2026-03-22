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
        $bot = new Bot($this->createMock(HttpClient::class));
        $listener = new BotEventListener($bot);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('pollTime must be between 1 and 60');

        $listener->listen(0);
    }

    public function testListenThrowsOnPollTimeTooHigh(): void
    {
        $bot = new Bot($this->createMock(HttpClient::class));
        $listener = new BotEventListener($bot);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('pollTime must be between 1 and 60');

        $listener->listen(61);
    }

    public function testHandlerRegistration(): void
    {
        $bot = new Bot($this->createMock(HttpClient::class));
        $listener = new BotEventListener($bot);

        $called = false;
        $listener->onMessage(function () use (&$called) {
            $called = true;
        });

        $this->assertFalse($called);
    }

    public function testCommandRegistration(): void
    {
        $bot = new Bot($this->createMock(HttpClient::class));
        $listener = new BotEventListener($bot);

        $called = false;
        $listener->onCommand('/start', function () use (&$called) {
            $called = true;
        });

        $this->assertFalse($called);
    }

    public function testDuplicateCommandRegistrationThrows(): void
    {
        $bot = new Bot($this->createMock(HttpClient::class));
        $listener = new BotEventListener($bot);

        $listener->onCommand('/start', function () {});

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Command handler for '/start' is already registered");

        $listener->onCommand('/start', function () {});
    }

    public function testCommandMatchDoesNotSkipRemainingEventsInBatch(): void
    {
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

        $listener->listen(1);

        $this->assertTrue($commandCalled, 'Command handler should be called');
        $this->assertTrue($messageCalled, 'onMessage handler should be called for second event in batch');
    }

    public function testOnExceptionCallbackReceivesExceptionAndEvent(): void
    {
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

        $listener->listen(
            pollTime: 1,
            onException: function (\Exception $e, EventDto $event) use (&$caughtException, &$caughtEvent) {
                $caughtException = $e;
                $caughtEvent = $event;
            },
        );

        $this->assertInstanceOf(\RuntimeException::class, $caughtException);
        $this->assertSame('handler error', $caughtException->getMessage());
        $this->assertSame(1, $caughtEvent->eventId);
    }

    public function testLoopContinuesAfterExceptionWithOnExceptionCallback(): void
    {
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
        $listener->listen(
            pollTime: 1,
            onException: function (\Exception $e) use (&$exceptions) {
                $exceptions[] = $e->getMessage();
            },
        );

        $this->assertSame([1, 2], $handlerCalls, 'Both events should be processed');
        $this->assertSame(['fail on first'], $exceptions, 'Only first event should trigger onException');
    }

    public function testExceptionBubblesWithoutOnExceptionCallback(): void
    {
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

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('unhandled');

        $listener->listen(1);
    }

    public function testStopFromHandlerStopsLoop(): void
    {
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

        $listener->listen(1);

        $this->assertSame(2, $pollCount, 'Loop should stop after handler calls stop()');
    }

    public function testUnknownEventTypeIsSkipped(): void
    {
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

        $listener->listen(1);

        $this->assertSame([2], $receivedEvents, 'Unknown event should be skipped, known event processed');
    }

    public function testLastEventIdIsPassedToNextPoll(): void
    {
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
        $listener->listen(1);

        $this->assertSame(0, $receivedLastEventIds[0], 'First poll should use lastEventId=0');
        $this->assertSame(42, $receivedLastEventIds[1], 'Second poll should use lastEventId from previous batch');
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('signalProvider')]
    public function testStopOnSignal(int $signal): void
    {
        if (!function_exists('pcntl_signal')) {
            $this->markTestSkipped('pcntl extension required');
        }

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
        $listener->listen(1);

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
