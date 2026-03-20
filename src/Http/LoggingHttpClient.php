<?php

declare(strict_types=1);

namespace BelkaTech\VkTeamsBot\Http;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

final readonly class LoggingHttpClient implements ClientInterface
{
    public function __construct(
        private ClientInterface $inner,
        private LoggerInterface $logger,
    ) {}

    public function sendRequest(
        RequestInterface $request,
    ): ResponseInterface {
        $this->logger->debug('[{method}] {path}', [
            'method' => $request->getMethod(),
            'path' => $request->getUri()->getPath(),
        ]);

        $response = $this->inner->sendRequest($request);

        $this->logger->debug((string) $response->getBody());
        $response->getBody()->rewind();

        return $response;
    }
}
