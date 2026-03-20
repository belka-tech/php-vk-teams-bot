<?php

declare(strict_types=1);

namespace BelkaTech\VkTeamsBot\Test\Unit;

use BelkaTech\VkTeamsBot\EventLoop;
use BelkaTech\VkTeamsBot\Http\HttpClient;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class EventLoopTest extends TestCase
{
    public function testFetchEventsThrowsOnPollTimeTooLow(): void
    {
        $httpClient = $this->createMock(HttpClient::class);
        $eventLoop = new EventLoop($httpClient);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('pollTime must be between 1 and 60');

        $eventLoop->fetchEvents(0);
    }

    public function testFetchEventsThrowsOnPollTimeTooHigh(): void
    {
        $httpClient = $this->createMock(HttpClient::class);
        $eventLoop = new EventLoop($httpClient);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('pollTime must be between 1 and 60');

        $eventLoop->fetchEvents(61);
    }

    public function testFetchEventsReturnsEvents(): void
    {
        $httpClient = $this->createMock(HttpClient::class);
        $httpClient->method('get')
            ->with('/events/get', $this->anything())
            ->willReturn([
                'events' => [
                    ['eventId' => 42, 'type' => 'newMessage', 'payload' => []],
                ],
            ]);

        $eventLoop = new EventLoop($httpClient);
        $events = $eventLoop->fetchEvents(5);

        $this->assertCount(1, $events);
        $this->assertSame(42, $events[0]['eventId']);
    }

    public function testFetchEventsReturnsEmptyOnRequestException(): void
    {
        $request = $this->createMock(\Psr\Http\Message\RequestInterface::class);

        $exception = new class ('timeout', $request) extends \RuntimeException implements \Psr\Http\Client\RequestExceptionInterface {
            public function __construct(
                string $message,
                private readonly \Psr\Http\Message\RequestInterface $request,
            ) {
                parent::__construct($message);
            }

            public function getRequest(): \Psr\Http\Message\RequestInterface
            {
                return $this->request;
            }
        };

        $httpClient = $this->createMock(HttpClient::class);
        $httpClient->method('get')->willThrowException($exception);

        $eventLoop = new EventLoop($httpClient);
        $events = $eventLoop->fetchEvents(5);

        $this->assertSame([], $events);
    }

    public function testFetchEventsReturnsEmptyOnTypeError(): void
    {
        $httpClient = $this->createMock(HttpClient::class);
        $httpClient->method('get')->willThrowException(new \TypeError('bad type'));

        $eventLoop = new EventLoop($httpClient);
        $events = $eventLoop->fetchEvents(5);

        $this->assertSame([], $events);
    }

    public function testHandlerRegistration(): void
    {
        $httpClient = $this->createMock(HttpClient::class);
        $eventLoop = new EventLoop($httpClient);

        $called = false;
        $eventLoop->onMessage(function () use (&$called) {
            $called = true;
        });

        // Verify no exception — handler was registered successfully
        $this->assertFalse($called);
    }

    public function testCommandRegistration(): void
    {
        $httpClient = $this->createMock(HttpClient::class);
        $eventLoop = new EventLoop($httpClient);

        $called = false;
        $eventLoop->onCommand('/start', function () use (&$called) {
            $called = true;
        });

        $this->assertFalse($called);
    }
}
