<?php

declare(strict_types=1);

namespace BelkaTech\VkTeamsBot\Api;

use BelkaTech\VkTeamsBot\Http\HttpClient;
use Psr\Http\Client\ClientExceptionInterface;

final readonly class SelfApi
{
    public function __construct(
        private HttpClient $httpClient,
    ) {}

    /**
     * @return array{
     *     ok: bool,
     *     userId: string,
     *     nick: string,
     *     firstName: string,
     *     lastName: string,
     *     about: string,
     *     photo: list<array{url: string}>,
     * }
     *
     * @throws ClientExceptionInterface
     */
    public function get(): array
    {
        /** @phpstan-ignore return.type */
        return $this->httpClient->get('/v1/self/get');
    }
}
