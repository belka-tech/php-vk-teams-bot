<?php

declare(strict_types=1);

namespace BelkaTech\VkTeamsBot;

use BelkaTech\VkTeamsBot\Api\ChatsApi;
use BelkaTech\VkTeamsBot\Api\EventsApi;
use BelkaTech\VkTeamsBot\Api\MessagesApi;
use BelkaTech\VkTeamsBot\Enum\ParseModeEnum;
use BelkaTech\VkTeamsBot\Http\HttpClient;

final class Bot
{
    public readonly MessagesApi $messages;
    public readonly ChatsApi $chats;
    public readonly EventsApi $events;

    public function __construct(
        HttpClient $httpClient,
        ParseModeEnum $parseMode = ParseModeEnum::Html,
    ) {
        $this->messages = new MessagesApi($httpClient, $parseMode);
        $this->chats = new ChatsApi($httpClient);
        $this->events = new EventsApi($httpClient);
    }
}
