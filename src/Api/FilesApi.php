<?php

declare(strict_types=1);

namespace BelkaTech\VkTeamsBot\Api;

use BelkaTech\VkTeamsBot\Http\HttpClient;
use Psr\Http\Client\ClientExceptionInterface;

final readonly class FilesApi
{
    public function __construct(
        private HttpClient $httpClient,
    ) {}

    /**
     * @return array{
     *     ok: bool,
     *     type: string,
     *     size: int,
     *     filename: string,
     *     url: string,
     * }
     *
     * @throws ClientExceptionInterface
     */
    public function getInfo(
        string $fileId,
    ): array {
        /** @phpstan-ignore return.type */
        return $this->httpClient->get('/v1/files/getInfo', [
            'fileId' => $fileId,
        ]);
    }
}
