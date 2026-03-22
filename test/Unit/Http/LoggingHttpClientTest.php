<?php

declare(strict_types=1);

namespace BelkaTech\VkTeamsBot\Test\Unit\Http;

use BelkaTech\VkTeamsBot\Http\LoggingHttpClient;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerInterface;

final class LoggingHttpClientTest extends TestCase
{
    public function testLogsRequestAndResponse(): void
    {
        // GIVEN: mocked PSR client, request, response, and logger
        $uri = $this->createMock(UriInterface::class);
        $uri->method('getPath')->willReturn('/messages/sendText');

        $request = $this->createMock(RequestInterface::class);
        $request->method('getMethod')->willReturn('GET');
        $request->method('getUri')->willReturn($uri);

        $body = $this->createMock(StreamInterface::class);
        $body->method('__toString')->willReturn('{"ok":true}');

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getBody')->willReturn($body);

        $inner = $this->createMock(ClientInterface::class);
        $inner->method('sendRequest')->willReturn($response);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->exactly(2))
            ->method('debug');

        $client = new LoggingHttpClient($inner, $logger);

        // WHEN: sendRequest is called
        $result = $client->sendRequest($request);

        // THEN: original response is returned and logger is called twice
        $this->assertSame($response, $result);
    }

    public function testRewindsResponseBody(): void
    {
        // GIVEN: mocked PSR client with body expecting rewind
        $uri = $this->createMock(UriInterface::class);
        $uri->method('getPath')->willReturn('/test');

        $request = $this->createMock(RequestInterface::class);
        $request->method('getMethod')->willReturn('GET');
        $request->method('getUri')->willReturn($uri);

        $body = $this->createMock(StreamInterface::class);
        $body->method('__toString')->willReturn('{}');
        $body->expects($this->once())->method('rewind');

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getBody')->willReturn($body);

        $inner = $this->createMock(ClientInterface::class);
        $inner->method('sendRequest')->willReturn($response);

        $logger = $this->createMock(LoggerInterface::class);

        $client = new LoggingHttpClient($inner, $logger);

        // WHEN: sendRequest is called
        $client->sendRequest($request);

        // THEN: body->rewind() is called (verified by expects above)
    }
}
