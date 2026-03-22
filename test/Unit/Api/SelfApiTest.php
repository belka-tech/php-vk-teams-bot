<?php

declare(strict_types=1);

namespace BelkaTech\VkTeamsBot\Test\Unit\Api;

use BelkaTech\VkTeamsBot\Api\SelfApi;
use BelkaTech\VkTeamsBot\Test\Spy\HttpClientSpy;
use PHPUnit\Framework\TestCase;

final class SelfApiTest extends TestCase
{
    private HttpClientSpy $httpClientSpy;

    private SelfApi $api;

    protected function setUp(): void
    {
        $this->httpClientSpy = new HttpClientSpy(
            getResponse: [
                'userId' => '123',
                'nick' => 'test_bot',
                'firstName' => 'Test',
                'lastName' => 'Bot',
                'about' => 'A test bot',
                'photo' => [['url' => 'https://example.com/photo.jpg']],
            ],
        );
        $this->api = new SelfApi($this->httpClientSpy);
    }

    public function testGet(): void
    {
        // WHEN: self/get is called
        $result = $this->api->get();

        // THEN: correct HTTP request is made and response returned
        $this->assertCount(1, $this->httpClientSpy->calls);
        [$method, $path, $params] = $this->httpClientSpy->calls[0];
        $this->assertSame('get', $method);
        $this->assertSame('/v1/self/get', $path);
        $this->assertSame([], $params);
        $this->assertSame('123', $result['userId']);
        $this->assertSame('test_bot', $result['nick']);
    }
}
