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
use BelkaTech\VkTeamsBot\Bot;
use BelkaTech\VkTeamsBot\Http\HttpClient;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;

$client = new Client();
$factory = new HttpFactory();

$httpClient = new HttpClient(
    baseUrl: 'https://api.icq.net/bot/v1',
    token: 'YOUR_BOT_TOKEN',
    client: $client,
    requestFactory: $factory,
    streamFactory: $factory,
);

$bot = new Bot($httpClient);

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

### Events (`$bot->events`)

Long polling for receiving events:

```php
$bot->events->onMessage(
    function (Bot $bot, array $event) {
        $bot->messages->sendText(
            chatId: $event['payload']['chat']['chatId'],
            text: 'Pong!',
        );
    },
);

$bot->events->onCommand(
    '/start',
    function (Bot $bot, array $event) {
        // handle /start command
    },
);

$bot->events->poll($bot, pollTime: 30);
```

### Keyboard

```php
use BelkaTech\VkTeamsBot\Keyboard\Button;
use BelkaTech\VkTeamsBot\Keyboard\Keyboard;
use BelkaTech\VkTeamsBot\Enum\ButtonStyleEnum;

$keyboard = new Keyboard();
$keyboard->addRow([
    new Button(
        text: 'OK',
        callbackData: 'confirm',
        style: ButtonStyleEnum::Primary,
    ),
    new Button(
        text: 'Cancel',
        callbackData: 'cancel',
        style: ButtonStyleEnum::Attention,
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
use BelkaTech\VkTeamsBot\Http\LoggingHttpClient;

$loggingClient = new LoggingHttpClient($psrHttpClient, $psrLogger);
```

## Parse Mode

`HTML` is used by default. You can switch to `MarkdownV2`:

```php
use BelkaTech\VkTeamsBot\Enum\ParseModeEnum;

$bot = new Bot($httpClient, ParseModeEnum::MarkdownV2);
```

You can also specify `parseMode` for an individual message:

```php
$bot->messages->sendText(
    chatId: '123456',
    text: '*bold*',
    parseMode: ParseModeEnum::MarkdownV2,
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
