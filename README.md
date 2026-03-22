# PHP VK Teams Bot API (aka ICQ Bot)

<p align="center">
<a href="https://packagist.org/packages/belka-tech/php-vk-teams-bot"><img src="https://img.shields.io/packagist/v/belka-tech/php-vk-teams-bot.svg?style=flat-square&label=latest%20version" alt="Latest Version"></a>
<a href="https://github.com/belka-tech/php-vk-teams-bot/actions/workflows/tests.yml"><img src="https://img.shields.io/github/actions/workflow/status/belka-tech/php-vk-teams-bot/tests.yml?style=flat-square" alt="Build"></a>
<a href="https://github.com/belka-tech/php-vk-teams-bot/blob/master/LICENSE"><img src="https://img.shields.io/github/license/belka-tech/php-vk-teams-bot.svg?style=flat-square" alt="License"></a>
</p>

## Introduction

PHP client for VK Teams Bot API. Provides a typed interface
for sending messages, managing chats, and receiving events via
long polling.

Official documentation: https://teams.vk.com/botapi/

## Requirements

- PHP 8.2+
- PSR-18 HTTP Client (`psr/http-client`)
- PSR-17 HTTP Factories (`psr/http-factory`)
- PSR-3 Logger (`psr/log`) — optional, for `LoggingHttpClient`

## Installation

```bash
composer require belka-tech/php-vk-teams-bot
```

## Quick Start

```php
$bot = new \BelkaTech\VkTeamsBot\Bot(
    new \BelkaTech\VkTeamsBot\Http\HttpClient(
        baseUri: 'https://api.icq.net/bot',
        token: 'YOUR_BOT_TOKEN',
        client: new \GuzzleHttp\Client(
            [
                'connect_timeout' => 4,
                'timeout' => 15,
                'http_errors' => false,
            ],
        ),
        requestFactory: new \GuzzleHttp\Psr7\HttpFactory(),
        streamFactory: new \GuzzleHttp\Psr7\HttpFactory(),
    ),
);

// Send a text message
$bot->messages->sendText(
    chatId: 'YOUR_CHAT_ID',
    text: '<b>Hello!</b>',
);
```

## API

### Messages (`$bot->messages`)

| Method                   | Description                       |
|--------------------------|-----------------------------------|
| `sendText()`             | Send a text message               |
| `sendFile()`             | Send a file                       |
| `sendVoice()`            | Send a voice message              |
| `editText()`             | Edit a message                    |
| `deleteMessages()`       | Delete messages                   |
| `answerCallbackQuery()`  | Answer a callback query           |
| `pinMessage()`           | Pin a message                     |
| `unpinMessage()`         | Unpin a message                   |
| `filesGetInfo()`         | Get file information              |

### Chats (`$bot->chats`)

| Method             | Description                        |
|--------------------|------------------------------------|
| `create()`         | Create a chat                      |
| `addMembers()`     | Add members                        |
| `removeMembers()`  | Remove members                     |
| `sendAction()`     | Send an action (typing, etc.)      |
| `getInfo()`        | Get chat information               |
| `getAdmins()`      | List administrators                |
| `getMembers()`     | List members                       |
| `blockUser()`      | Block a user                       |
| `unblockUser()`    | Unblock a user                     |
| `resolvePending()` | Approve/reject join requests       |
| `setTitle()`       | Set chat title                     |
| `setAvatar()`      | Set chat avatar                    |
| `setAbout()`       | Set chat description               |
| `setRules()`       | Set chat rules                     |

### Events API (`$bot->events`)

| Method  | Description                        |
|---------|------------------------------------|
| `get()` | Fetch events (long polling)        |

### Event Listener

Long polling with event dispatching:

```php
$botEventListener = new \BelkaTech\VkTeamsBot\BotEventListener(
    bot: $bot,
);

// Register event handlers
$botEventListener->onMessage(
    function (
        \BelkaTech\VkTeamsBot\Bot $bot,
        \BelkaTech\VkTeamsBot\Event\EventDto $event,
    ): void {
        $bot->messages->sendText(
            chatId: $event->payload['chat']['chatId'],
            text: 'Pong!',
        );
    },
);

$botEventListener->onCommand(
    '/start',
    function (
        \BelkaTech\VkTeamsBot\Bot $bot,
        \BelkaTech\VkTeamsBot\Event\EventDto $event,
    ): void {
        // handle /start command
    },
);

// Start long polling (must be called after all handlers are registered)
$botEventListener->listen(
    pollTime: 30,
    onException: function (
        \Exception $exception,
        \BelkaTech\VkTeamsBot\Event\EventDto $event
    ): void {
        // Log the error
        $this->logger->error('Some text', [
            'event_id' => $event->eventId,
            'event_type' => $event->type,
            'event_payload' => $event->payload,
            'exception' => $exception,
        ]);
        error_log($exception->getMessage());
        
        // Or catch exception to an error reporting system
        $this->sentry->captureException($exception);
        
        // On exception loop continues,
        // you can re-throw the exception to force stop the loop
        throw $exception;
    },
);

// Stop the listener programmatically (e.g. from a handler)
$botEventListener->stop();
```

| Method              | Description                        |
|---------------------|------------------------------------|
| `onCommand()`       | Register a command handler         |
| `onMessage()`       | Handle new messages                |
| `onEditedMessage()` | Handle edited messages             |
| `onDeletedMessage()`| Handle deleted messages            |
| `onPinnedMessage()` | Handle pinned messages             |
| `onUnpinnedMessage()`| Handle unpinned messages          |
| `onNewChatMember()` | Handle new chat members            |
| `onLeftChatMember()`| Handle members leaving             |
| `onCallbackQuery()` | Handle callback queries            |
| `listen()`          | Start long polling                 |
| `stop()`            | Stop the listener                  |

If the `pcntl` extension is available, `SIGTERM` and `SIGINT` signals are handled automatically for graceful shutdown.
Without `pcntl`, use `$botEventListener->stop()` from a handler to stop the loop.

### Keyboard

```php
$keyboard = new \BelkaTech\VkTeamsBot\Keyboard\Keyboard();
$keyboard->addRow([
    new \BelkaTech\VkTeamsBot\Keyboard\Button(
        text: 'OK',
        callbackData: 'confirm',
        style: \BelkaTech\VkTeamsBot\Enum\ButtonStyleEnum::Primary,
    ),
    new \BelkaTech\VkTeamsBot\Keyboard\Button(
        text: 'Cancel',
        callbackData: 'cancel',
        style: \BelkaTech\VkTeamsBot\Enum\ButtonStyleEnum::Attention,
    ),
]);

$bot->messages->sendText(
    chatId: '123456',
    text: 'Confirm?',
    inlineKeyboardMarkup: $keyboard,
);
```

### LoggingHttpClient

Decorator for a PSR-18 client that logs requests and responses:

```php
$loggingClient = new \BelkaTech\VkTeamsBot\Http\LoggingHttpClient(
    $psrHttpClient,
    $psrLogger,
);
```

## Parse Mode

`HTML` is used by default. You can switch to `MarkdownV2`:

```php
$bot = new \BelkaTech\VkTeamsBot\Bot(
    httpClient: $httpClient,
    parseMode: \BelkaTech\VkTeamsBot\Enum\ParseModeEnum::MarkdownV2,
);
```

You can also specify `parseMode` for an individual message:

```php
$bot->messages->sendText(
    chatId: '123456',
    text: '**bold**',
    parseMode: \BelkaTech\VkTeamsBot\Enum\ParseModeEnum::MarkdownV2,
);
```

## Development

```bash
make setup   # build image, start container, install dependencies
make test    # run tests
make phpstan # run static analysis
make shell   # enter the container
```

Other commands: `make up`, `make down`, `make build`, `make install`.

## Alternatives

- https://github.com/dasshit/php-icqbot
- https://github.com/mail-ru-im/bot-java
- https://github.com/mail-ru-im/bot-python
- https://github.com/mail-ru-im/bot-golang

## License

- `PHP VK Teams Bot` package is open-sourced software licensed under the [MIT license](LICENSE) by [BelkaCar](https://belkacar.ru).
