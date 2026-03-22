<?php

declare(strict_types=1);

namespace BelkaTech\VkTeamsBot\Http;

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class HttpClient
{
    public function __construct(
        private readonly string $baseUri,
        private readonly string $token,
        private readonly ClientInterface $client,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface $streamFactory,
    ) {}

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     *
     * @throws ClientExceptionInterface
     */
    public function get(
        string $path,
        array $params = [],
    ): array {
        $params['token'] = $this->token;
        $query = http_build_query($this->filterParams($params));
        $url = $this->baseUri . $path . '?' . $query;

        $request = $this->requestFactory->createRequest('GET', $url);
        $response = $this->client->sendRequest($request);
        $this->assertSuccessResponse($response);
        $body = (string)$response->getBody();

        /** @var array<string, mixed> */
        return json_decode(
            $body,
            associative: true,
            flags: JSON_THROW_ON_ERROR,
        );
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     *
     * @throws ClientExceptionInterface
     */
    public function postMultipart(
        string $path,
        array $params,
        string $filePath,
    ): array {
        $params['token'] = $this->token;
        $query = http_build_query($this->filterParams($params));
        $url = $this->baseUri . $path . '?' . $query;

        $boundary = bin2hex(random_bytes(16));

        $body = "--{$boundary}\r\n"
            . 'Content-Disposition: form-data; name="file"; filename="'
            . basename($filePath) . '"' . "\r\n"
            . "Content-Type: application/octet-stream\r\n\r\n"
            . file_get_contents($filePath) . "\r\n"
            . "--{$boundary}--\r\n";

        $request = $this->requestFactory
            ->createRequest('POST', $url)
            ->withHeader('Content-Type', 'multipart/form-data; boundary=' . $boundary)
            ->withBody($this->streamFactory->createStream($body));

        $response = $this->client->sendRequest($request);
        $this->assertSuccessResponse($response);
        $result = (string)$response->getBody();

        /** @var array<string, mixed> */
        return json_decode(
            $result,
            associative: true,
            flags: JSON_THROW_ON_ERROR,
        );
    }

    private function assertSuccessResponse(
        ResponseInterface $response,
    ): void {
        $statusCode = $response->getStatusCode();
        if ($statusCode >= 400) {
            $body = (string)$response->getBody();
            $response->getBody()->rewind();
            throw new \RuntimeException(
                sprintf('VK Teams API error: HTTP %d — %s', $statusCode, $body),
            );
        }
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    private function filterParams(
        array $params,
    ): array {
        return array_filter(
            $params,
            static fn(mixed $value): bool => $value !== null,
        );
    }
}
