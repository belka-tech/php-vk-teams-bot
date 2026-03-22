<?php

declare(strict_types=1);

namespace BelkaTech\VkTeamsBot;

use BelkaTech\VkTeamsBot\Enum\EventTypeEnum;
use BelkaTech\VkTeamsBot\Event\EventDto;
use Psr\Http\Client\NetworkExceptionInterface;

final class BotEventListener
{
    private bool $isRunning = false;
    private int $lastEventId = 0;

    /** @var array<string, list<\Closure(Bot, EventDto): void>> */
    private array $handlers = [];

    /** @var array<string, \Closure(Bot, EventDto): void> */
    private array $commandHandlers = [];

    public function __construct(
        private readonly Bot $bot,
    ) {}

    /**
     * @param \Closure(Bot, EventDto): void $handler
     */
    public function onCommand(
        string $command,
        \Closure $handler,
    ): void {
        if (isset($this->commandHandlers[$command])) {
            throw new \InvalidArgumentException(
                "Command handler for '{$command}' is already registered",
            );
        }

        $this->commandHandlers[$command] = $handler;
    }

    /**
     * @param \Closure(Bot, EventDto): void $handler
     */
    public function onMessage(
        \Closure $handler,
    ): void {
        $this->on(EventTypeEnum::MessageNew, $handler);
    }

    /**
     * @param \Closure(Bot, EventDto): void $handler
     */
    public function onEditedMessage(
        \Closure $handler,
    ): void {
        $this->on(EventTypeEnum::MessageEdited, $handler);
    }

    /**
     * @param \Closure(Bot, EventDto): void $handler
     */
    public function onDeletedMessage(
        \Closure $handler,
    ): void {
        $this->on(EventTypeEnum::MessageDeleted, $handler);
    }

    /**
     * @param \Closure(Bot, EventDto): void $handler
     */
    public function onPinnedMessage(
        \Closure $handler,
    ): void {
        $this->on(EventTypeEnum::MessagePinned, $handler);
    }

    /**
     * @param \Closure(Bot, EventDto): void $handler
     */
    public function onUnpinnedMessage(
        \Closure $handler,
    ): void {
        $this->on(EventTypeEnum::MessageUnpinned, $handler);
    }

    /**
     * @param \Closure(Bot, EventDto): void $handler
     */
    public function onNewChatMember(
        \Closure $handler,
    ): void {
        $this->on(EventTypeEnum::ChatMemberJoined, $handler);
    }

    /**
     * @param \Closure(Bot, EventDto): void $handler
     */
    public function onLeftChatMember(
        \Closure $handler,
    ): void {
        $this->on(EventTypeEnum::ChatMemberLeft, $handler);
    }

    /**
     * @param \Closure(Bot, EventDto): void $handler
     */
    public function onCallbackQuery(
        \Closure $handler,
    ): void {
        $this->on(EventTypeEnum::CallbackQuery, $handler);
    }

    public function stop(): void
    {
        $this->isRunning = false;
    }

    /**
     * @param int $pollTime Maximum polling request duration (1-60 sec)
     * @param ?\Closure(\Exception, EventDto): void $onException
     */
    public function listen(
        int $pollTime,
        ?\Closure $onException = null,
    ): void {
        if ($pollTime < 1 || $pollTime > 60) {
            throw new \InvalidArgumentException(
                "pollTime must be between 1 and 60 seconds, got {$pollTime}",
            );
        }

        if (function_exists('pcntl_signal')) {
            pcntl_async_signals(true);
            pcntl_signal(SIGTERM, fn() => $this->stop());
            pcntl_signal(SIGINT, fn() => $this->stop());
        }

        $this->isRunning = true;

        while ($this->isRunning) {
            foreach ($this->fetchEvents($pollTime) as $event) {
                $eventType = EventTypeEnum::tryFrom($event['type']);
                if ($eventType === null) {
                    continue;
                }

                $eventDto = new EventDto(
                    eventId: $event['eventId'],
                    type: $eventType,
                    payload: $event['payload'],
                );

                try {
                    $this->handleEvent($eventDto);
                } catch (\Exception $e) {
                    if ($onException === null) {
                        throw $e;
                    }

                    $onException($e, $eventDto);
                }
            }
        }
    }

    private function handleEvent(
        EventDto $event,
    ): void {
        if ($event->type === EventTypeEnum::MessageNew) {
            $text = isset($event->payload['text']) && is_string($event->payload['text'])
                ? $event->payload['text']
                : '';

            $commandHandler = $this->matchCommand($text);
            if ($commandHandler !== null) {
                $commandHandler($this->bot, $event);

                return;
            }
        }

        if (array_key_exists($event->type->value, $this->handlers)) {
            foreach ($this->handlers[$event->type->value] as $handler) {
                $handler($this->bot, $event);
            }
        }
    }

    /**
     * @return (\Closure(Bot, EventDto): void)|null
     */
    private function matchCommand(
        string $text,
    ): ?\Closure {
        foreach ($this->commandHandlers as $command => $handler) {
            if (
                $text === $command
                || str_starts_with($text, $command . ' ')
                || str_starts_with($text, $command . '@')
            ) {
                return $handler;
            }
        }

        return null;
    }

    /**
     * @param int $pollTime Maximum polling request duration (1-60 sec)
     * @return list<array{
     *     eventId: int,
     *     type: string,
     *     payload: array<string, mixed>,
     * }>
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
        } catch (NetworkExceptionInterface) {
            // Delay to keep the loop rhythm similar to a normal poll cycle —
            // fast failures like DNS/connect errors would otherwise spin the loop.
            sleep($pollTime);

            return [];
        }
    }

    /**
     * @param \Closure(Bot, EventDto): void $handler
     */
    private function on(
        EventTypeEnum $eventType,
        \Closure $handler,
    ): void {
        $this->handlers[$eventType->value][] = $handler;
    }
}
