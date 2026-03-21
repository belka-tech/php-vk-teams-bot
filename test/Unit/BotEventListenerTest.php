<?php

declare(strict_types=1);

namespace BelkaTech\VkTeamsBot\Test\Unit;

use BelkaTech\VkTeamsBot\Bot;
use BelkaTech\VkTeamsBot\BotEventListener;
use BelkaTech\VkTeamsBot\Http\HttpClient;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class BotEventListenerTest extends TestCase
{
    public function testListenThrowsOnPollTimeTooLow(): void
    {
        $bot = new Bot($this->createMock(HttpClient::class));
        $listener = new BotEventListener($bot);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('pollTime must be between 1 and 60');

        $listener->listen(0);
    }

    public function testListenThrowsOnPollTimeTooHigh(): void
    {
        $bot = new Bot($this->createMock(HttpClient::class));
        $listener = new BotEventListener($bot);

        $this->expectException(InvalidArgumentException::class);
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
}
