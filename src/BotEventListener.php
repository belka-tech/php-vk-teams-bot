<?php

declare(strict_types=1);

namespace BelkaTech\VkTeamsBot;

use BelkaTech\VkTeamsBot\Enum\EventTypeEnum;
use InvalidArgumentException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\RequestExceptionInterface;
use TypeError;

final class BotEventListener
{
    private int $lastEventId = 0;

    /** @var array<string, list<callable>> */
    private array $handlers = [];

    /** @var array<string, callable> */
    private array $commandHandlers = [];

    public function __construct(
        private readonly Bot $bot,
    ) {}

    public function onCommand(
        string $command,
        callable $handler,
    ): void {
        $this->commandHandlers[$command] = $handler;
    }

    public function onMessage(
        callable $handler,
    ): void {
        $this->on(EventTypeEnum::MessageNew, $handler);
    }

    public function onEditedMessage(
        callable $handler,
    ): void {
        $this->on(EventTypeEnum::MessageEdited, $handler);
    }

    public function onDeletedMessage(
        callable $handler,
    ): void {
        $this->on(EventTypeEnum::MessageDeleted, $handler);
    }

    public function onPinnedMessage(
        callable $handler,
    ): void {
        $this->on(EventTypeEnum::MessagePinned, $handler);
    }

    public function onUnpinnedMessage(
        callable $handler,
    ): void {
        $this->on(EventTypeEnum::MessageUnpinned, $handler);
    }

    public function onNewChatMember(
        callable $handler,
    ): void {
        $this->on(EventTypeEnum::ChatMemberJoined, $handler);
    }

    public function onLeftChatMember(
        callable $handler,
    ): void {
        $this->on(EventTypeEnum::ChatMemberLeft, $handler);
    }

    public function onCallbackQuery(
        callable $handler,
    ): void {
        $this->on(EventTypeEnum::CallbackQuery, $handler);
    }

    /**
     * @param int $pollTime Maximum polling request duration (1-60 sec)
     */
    public function listen(
        int $pollTime,
    ): void {
        if ($pollTime < 1 || $pollTime > 60) {
            throw new InvalidArgumentException(
                "pollTime must be between 1 and 60 seconds, got {$pollTime}",
            );
        }

        while (true) { /** @phpstan-ignore while.alwaysTrue */
            foreach ($this->fetchEvents($pollTime) as $event) {
                if ($event['type'] === EventTypeEnum::MessageNew->value) {
                    $text = isset($event['payload']['text']) && is_string($event['payload']['text'])
                        ? $event['payload']['text']
                        : '';
                    foreach ($this->commandHandlers as $command => $handler) {
                        if (str_starts_with($text, $command)) {
                            $handler($this->bot, $event);

                            continue 3;
                        }
                    }
                }

                if (array_key_exists($event['type'], $this->handlers)) {
                    foreach ($this->handlers[$event['type']] as $handler) {
                        $handler($this->bot, $event);
                    }
                }
            }
        }
    }

    /**
     * @param int $pollTime Maximum polling request duration (1-60 sec)
     * @return list<array{
     *     eventId: int,
     *     type: string,
     *     payload: array<string, mixed>,
     * }>
     *
     * @throws ClientExceptionInterface
     */
    private function fetchEvents(
        int $pollTime,
    ): array {
        try {
            $events = $this->bot->events->get($this->lastEventId, $pollTime);

            $eventList = $events['events'];

            if ($eventList !== []) {
                $lastEvent = end($eventList);
                $this->lastEventId = (int)$lastEvent['eventId'];
            }

            return $eventList;
        } catch (TypeError | RequestExceptionInterface $e) {
            return [];
        }
    }

    private function on(
        EventTypeEnum $eventType,
        callable $handler,
    ): void {
        $this->handlers[$eventType->value][] = $handler;
    }
}
