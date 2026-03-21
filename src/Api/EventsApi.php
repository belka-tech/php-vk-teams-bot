<?php

declare(strict_types=1);

namespace BelkaTech\VkTeamsBot\Api;

use BelkaTech\VkTeamsBot\Http\HttpClient;
use Psr\Http\Client\ClientExceptionInterface;

final readonly class EventsApi
{
    public function __construct(
        private HttpClient $httpClient,
    ) {}

    /**
     * @return array{
     *     events: list<array{
     *         eventId: int,
     *         type: string,
     *         payload: array<string, mixed>,
     *     }>,
     * }
     *
     * @throws ClientExceptionInterface
     */
    public function get(
        int $lastEventId,
        int $pollTime,
    ): array {
        /** @phpstan-ignore return.type */
        return $this->httpClient->get('/v1/events/get', [
            'lastEventId' => $lastEventId,
            'pollTime' => $pollTime,
        ]);
    }
}
