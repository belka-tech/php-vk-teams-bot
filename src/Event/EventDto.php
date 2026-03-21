<?php

declare(strict_types=1);

namespace BelkaTech\VkTeamsBot\Event;

use BelkaTech\VkTeamsBot\Enum\EventTypeEnum;

final class EventDto
{
    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        public readonly int $eventId,
        public readonly EventTypeEnum $type,
        public readonly array $payload,
    ) {}
}
