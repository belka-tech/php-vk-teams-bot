<?php

declare(strict_types=1);

namespace BelkaTech\VkTeamsBot\Test\Spy;

use BelkaTech\VkTeamsBot\Http\HttpClient;

final class HttpClientSpy extends HttpClient
{
    /**
     * @var list<array{
     *     string,
     *     string,
     *     array<string, mixed>,
     * }|array{
     *     string,
     *     string,
     *     array<string, mixed>,
     *     string,
     * }>
     */
    public array $calls = [];

    /** @var array<string, mixed> */
    private array $getResponse;

    /** @var array<string, mixed> */
    private array $postMultipartResponse;

    /**
     * @param array<string, mixed> $getResponse
     * @param array<string, mixed> $postMultipartResponse
     */
    public function __construct(
        array $getResponse = ['ok' => true],
        array $postMultipartResponse = ['ok' => true],
    ) {
        $this->getResponse = $getResponse;
        $this->postMultipartResponse = $postMultipartResponse;
    }

    public function get(
        string $path,
        array $params = [],
    ): array {
        $this->calls[] = ['get', $path, $params];

        return $this->getResponse;
    }

    public function postMultipart(
        string $path,
        array $params,
        string $filePath,
    ): array {
        $this->calls[] = ['postMultipart', $path, $params, $filePath];

        return $this->postMultipartResponse;
    }
}
