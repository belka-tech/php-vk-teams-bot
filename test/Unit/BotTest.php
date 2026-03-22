<?php

declare(strict_types=1);

namespace BelkaTech\VkTeamsBot\Test\Unit;

use BelkaTech\VkTeamsBot\Api\ChatsApi;
use BelkaTech\VkTeamsBot\Api\EventsApi;
use BelkaTech\VkTeamsBot\Api\MessagesApi;
use BelkaTech\VkTeamsBot\Bot;
use BelkaTech\VkTeamsBot\Enum\ParseModeEnum;
use BelkaTech\VkTeamsBot\Http\HttpClient;
use PHPUnit\Framework\TestCase;

final class BotTest extends TestCase
{
    public function testConstructorCreatesApiInstances(): void
    {
        // GIVEN: a mocked HttpClient
        $httpClient = $this->createMock(HttpClient::class);

        // WHEN: Bot is constructed with default params
        $bot = new Bot($httpClient);

        // THEN: all API instances are created
        $this->assertInstanceOf(MessagesApi::class, $bot->messages);
        $this->assertInstanceOf(ChatsApi::class, $bot->chats);
        $this->assertInstanceOf(EventsApi::class, $bot->events);
    }

    public function testConstructorAcceptsCustomParseMode(): void
    {
        // GIVEN: a mocked HttpClient
        $httpClient = $this->createMock(HttpClient::class);

        // WHEN: Bot is constructed with custom parse mode
        $bot = new Bot($httpClient, ParseModeEnum::MarkdownV2);

        // THEN: Bot is created successfully
        $this->assertInstanceOf(MessagesApi::class, $bot->messages);
    }
}
