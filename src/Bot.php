<?php

declare(strict_types=1);

namespace BelkaTech\VkTeamsBot;

use BelkaTech\VkTeamsBot\Api\ChatsApi;
use BelkaTech\VkTeamsBot\Api\EventsApi;
use BelkaTech\VkTeamsBot\Api\FilesApi;
use BelkaTech\VkTeamsBot\Api\MessagesApi;
use BelkaTech\VkTeamsBot\Api\SelfApi;
use BelkaTech\VkTeamsBot\Enum\ParseModeEnum;
use BelkaTech\VkTeamsBot\Http\HttpClient;

final class Bot
{
    public readonly SelfApi $self;
    public readonly MessagesApi $messages;
    public readonly ChatsApi $chats;
    public readonly FilesApi $files;
    public readonly EventsApi $events;

    public function __construct(
        HttpClient $httpClient,
        ParseModeEnum $parseMode = ParseModeEnum::Html,
    ) {
        $this->self = new SelfApi($httpClient);
        $this->messages = new MessagesApi($httpClient, $parseMode);
        $this->chats = new ChatsApi($httpClient);
        $this->files = new FilesApi($httpClient);
        $this->events = new EventsApi($httpClient);
    }
}
