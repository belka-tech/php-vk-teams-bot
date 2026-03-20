<?php

declare(strict_types=1);

namespace BelkaTech\VkTeamsBot\Test\Unit\Http;

use BelkaTech\VkTeamsBot\Http\HttpClient;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

final class HttpClientTest extends TestCase
{
    public function testGetBuildsCorrectUrlAndReturnsDecodedJson(): void
    {
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('__toString')
            ->willReturn('{"ok":true}');

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getBody')->willReturn($stream);
        $response->method('getStatusCode')->willReturn(200);

        $request = $this->createMock(RequestInterface::class);

        $psrClient = $this->createMock(ClientInterface::class);
        $psrClient->method('sendRequest')->willReturn($response);

        $requestFactory = $this->createMock(RequestFactoryInterface::class);
        $requestFactory->expects($this->once())
            ->method('createRequest')
            ->with('GET', $this->callback(function (string $url): bool {
                $this->assertStringStartsWith(
                    'https://api.icq.net/bot/v1/messages/sendText?',
                    $url,
                );
                $this->assertStringContainsString('token=test-token', $url);
                $this->assertStringContainsString('chatId=123', $url);
                $this->assertStringContainsString('text=hello', $url);
                return true;
            }))
            ->willReturn($request);

        $streamFactory = $this->createMock(StreamFactoryInterface::class);

        $httpClient = new HttpClient(
            baseUri: 'https://api.icq.net/bot/v1',
            token: 'test-token',
            client: $psrClient,
            requestFactory: $requestFactory,
            streamFactory: $streamFactory,
        );

        $result = $httpClient->get('/messages/sendText', [
            'chatId' => '123',
            'text' => 'hello',
        ]);

        $this->assertSame(['ok' => true], $result);
    }

    public function testGetFiltersNullParams(): void
    {
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('__toString')->willReturn('{"ok":true}');

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getBody')->willReturn($stream);
        $response->method('getStatusCode')->willReturn(200);

        $request = $this->createMock(RequestInterface::class);

        $psrClient = $this->createMock(ClientInterface::class);
        $psrClient->method('sendRequest')->willReturn($response);

        $requestFactory = $this->createMock(RequestFactoryInterface::class);
        $requestFactory->expects($this->once())
            ->method('createRequest')
            ->with('GET', $this->callback(function (string $url): bool {
                $this->assertStringNotContainsString('replyMsgId', $url);
                $this->assertStringNotContainsString('nullParam', $url);
                return true;
            }))
            ->willReturn($request);

        $streamFactory = $this->createMock(StreamFactoryInterface::class);

        $httpClient = new HttpClient(
            baseUri: 'https://api.icq.net/bot/v1',
            token: 'test-token',
            client: $psrClient,
            requestFactory: $requestFactory,
            streamFactory: $streamFactory,
        );

        $httpClient->get('/messages/sendText', [
            'chatId' => '123',
            'replyMsgId' => null,
            'nullParam' => 'null',
        ]);
    }

    public function testGetAlwaysAddsToken(): void
    {
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('__toString')->willReturn('{}');

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getBody')->willReturn($stream);
        $response->method('getStatusCode')->willReturn(200);

        $request = $this->createMock(RequestInterface::class);

        $psrClient = $this->createMock(ClientInterface::class);
        $psrClient->method('sendRequest')->willReturn($response);

        $requestFactory = $this->createMock(RequestFactoryInterface::class);
        $requestFactory->expects($this->once())
            ->method('createRequest')
            ->with('GET', $this->callback(function (string $url): bool {
                $this->assertStringContainsString('token=my-secret', $url);
                return true;
            }))
            ->willReturn($request);

        $streamFactory = $this->createMock(StreamFactoryInterface::class);

        $httpClient = new HttpClient(
            baseUri: 'https://api.example.com',
            token: 'my-secret',
            client: $psrClient,
            requestFactory: $requestFactory,
            streamFactory: $streamFactory,
        );

        $httpClient->get('/self/get');
    }

    public function testGetThrowsOnClientError(): void
    {
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('__toString')->willReturn('{"description":"Invalid token"}');

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getBody')->willReturn($stream);
        $response->method('getStatusCode')->willReturn(401);

        $request = $this->createMock(RequestInterface::class);

        $psrClient = $this->createMock(ClientInterface::class);
        $psrClient->method('sendRequest')->willReturn($response);

        $requestFactory = $this->createMock(RequestFactoryInterface::class);
        $requestFactory->method('createRequest')->willReturn($request);

        $streamFactory = $this->createMock(StreamFactoryInterface::class);

        $httpClient = new HttpClient(
            baseUri: 'https://api.example.com',
            token: 'bad-token',
            client: $psrClient,
            requestFactory: $requestFactory,
            streamFactory: $streamFactory,
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('VK Teams API error: HTTP 401');

        $httpClient->get('/self/get');
    }

    public function testGetThrowsOnServerError(): void
    {
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('__toString')->willReturn('Internal Server Error');

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getBody')->willReturn($stream);
        $response->method('getStatusCode')->willReturn(500);

        $request = $this->createMock(RequestInterface::class);

        $psrClient = $this->createMock(ClientInterface::class);
        $psrClient->method('sendRequest')->willReturn($response);

        $requestFactory = $this->createMock(RequestFactoryInterface::class);
        $requestFactory->method('createRequest')->willReturn($request);

        $streamFactory = $this->createMock(StreamFactoryInterface::class);

        $httpClient = new HttpClient(
            baseUri: 'https://api.example.com',
            token: 'test-token',
            client: $psrClient,
            requestFactory: $requestFactory,
            streamFactory: $streamFactory,
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('VK Teams API error: HTTP 500 — Internal Server Error');

        $httpClient->get('/messages/sendText');
    }
}
