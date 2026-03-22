<?php

declare(strict_types=1);

namespace BelkaTech\VkTeamsBot\Test\Unit\Api;

use BelkaTech\VkTeamsBot\Api\EventsApi;
use BelkaTech\VkTeamsBot\Test\Spy\HttpClientSpy;
use PHPUnit\Framework\TestCase;

final class EventsApiTest extends TestCase
{
    private HttpClientSpy $httpClientSpy;

    private EventsApi $api;

    protected function setUp(): void
    {
        $this->httpClientSpy = new HttpClientSpy(
            getResponse: ['events' => []],
        );
        $this->api = new EventsApi($this->httpClientSpy);
    }

    public function testGet(): void
    {
        // WHEN: events/get is called
        $result = $this->api->get(lastEventId: 42, pollTime: 30);

        // THEN: correct HTTP request is made and response returned
        $this->assertCount(1, $this->httpClientSpy->calls);
        [$method, $path, $params] = $this->httpClientSpy->calls[0];
        $this->assertSame('get', $method);
        $this->assertSame('/v1/events/get', $path);
        $this->assertSame(42, $params['lastEventId']);
        $this->assertSame(30, $params['pollTime']);
        $this->assertSame(['events' => []], $result);
    }
}
