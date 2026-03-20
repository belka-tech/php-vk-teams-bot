<?php

declare(strict_types=1);

namespace BelkaTech\VkTeamsBot\Test\Unit;

use BelkaTech\VkTeamsBot\Api\ChatsApi;
use BelkaTech\VkTeamsBot\Api\MessagesApi;
use BelkaTech\VkTeamsBot\Bot;
use BelkaTech\VkTeamsBot\Enum\ParseModeEnum;
use BelkaTech\VkTeamsBot\EventLoop;
use BelkaTech\VkTeamsBot\Http\HttpClient;
use PHPUnit\Framework\TestCase;

final class BotTest extends TestCase
{
    public function testConstructorCreatesApiInstances(): void
    {
        $httpClient = $this->createMock(HttpClient::class);

        $bot = new Bot($httpClient);

        $this->assertInstanceOf(MessagesApi::class, $bot->messages);
        $this->assertInstanceOf(ChatsApi::class, $bot->chats);
        $this->assertInstanceOf(EventLoop::class, $bot->events);
    }

    public function testConstructorAcceptsCustomParseMode(): void
    {
        $httpClient = $this->createMock(HttpClient::class);

        $bot = new Bot($httpClient, ParseModeEnum::MarkdownV2);

        $this->assertInstanceOf(MessagesApi::class, $bot->messages);
    }
}
