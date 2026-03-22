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
        // GIVEN: PSR mocks returning {"ok":true}
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

        // WHEN: get is called with params
        $result = $httpClient->get('/messages/sendText', [
            'chatId' => '123',
            'text' => 'hello',
        ]);

        // THEN: decoded JSON is returned
        $this->assertSame(['ok' => true], $result);
    }

    public function testGetFiltersNullParams(): void
    {
        // GIVEN: PSR mocks returning success
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
                // THEN: null param is not in URL
                $this->assertStringNotContainsString('replyMsgId', $url);
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

        // WHEN: get is called with a null param
        $httpClient->get('/messages/sendText', [
            'chatId' => '123',
            'replyMsgId' => null,
        ]);
    }

    public function testGetAlwaysAddsToken(): void
    {
        // GIVEN: PSR mocks
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
                // THEN: token is in URL
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

        // WHEN: get is called without extra params
        $httpClient->get('/self/get');
    }

    public function testGetDoesNotFilterNullString(): void
    {
        // GIVEN: PSR mocks
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
                // THEN: string "null" is preserved in URL
                $this->assertStringContainsString('stringParam=null', $url);
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

        // WHEN: get is called with string "null" param
        $httpClient->get('/messages/sendText', [
            'chatId' => '123',
            'stringParam' => 'null',
        ]);
    }

    public function testFilterParamsRemovesNullButKeepsOtherFalsyValues(): void
    {
        // GIVEN: PSR mocks
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
                parse_str(parse_url($url, PHP_URL_QUERY), $query);

                // THEN: null is filtered, falsy non-null values are preserved
                $this->assertArrayNotHasKey('nullParam', $query);
                $this->assertArrayHasKey('emptyString', $query);
                $this->assertSame('', $query['emptyString']);
                $this->assertArrayHasKey('zero', $query);
                $this->assertSame('0', $query['zero']);
                $this->assertArrayHasKey('false', $query);
                $this->assertArrayHasKey('stringNull', $query);
                $this->assertSame('null', $query['stringNull']);

                return true;
            }))
            ->willReturn($request);

        $streamFactory = $this->createMock(StreamFactoryInterface::class);

        $httpClient = new HttpClient(
            baseUri: 'https://api.example.com',
            token: 'test-token',
            client: $psrClient,
            requestFactory: $requestFactory,
            streamFactory: $streamFactory,
        );

        // WHEN: get is called with mixed null and falsy params
        $httpClient->get('/test', [
            'nullParam' => null,
            'emptyString' => '',
            'zero' => 0,
            'false' => false,
            'stringNull' => 'null',
        ]);
    }

    public function testPostMultipartThrowsWhenFileNotFound(): void
    {
        // GIVEN: an HttpClient instance
        $streamFactory = $this->createMock(StreamFactoryInterface::class);
        $requestFactory = $this->createMock(RequestFactoryInterface::class);
        $psrClient = $this->createMock(ClientInterface::class);

        $httpClient = new HttpClient(
            baseUri: 'https://api.example.com',
            token: 'test-token',
            client: $psrClient,
            requestFactory: $requestFactory,
            streamFactory: $streamFactory,
        );

        // THEN: exception is expected
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('File not found: /nonexistent/file.txt');

        // WHEN: postMultipart is called with non-existent file
        $httpClient->postMultipart('/upload', [], '/nonexistent/file.txt');
    }

    public function testGetThrowsOnClientError(): void
    {
        // GIVEN: PSR mocks returning 401
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

        // THEN: RuntimeException is expected
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('VK Teams API error: HTTP 401');

        // WHEN: get is called
        $httpClient->get('/self/get');
    }

    public function testGetThrowsOnServerError(): void
    {
        // GIVEN: PSR mocks returning 500
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

        // THEN: RuntimeException is expected
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('VK Teams API error: HTTP 500 — Internal Server Error');

        // WHEN: get is called
        $httpClient->get('/messages/sendText');
    }
}
