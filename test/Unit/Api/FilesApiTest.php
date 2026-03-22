<?php

declare(strict_types=1);

namespace BelkaTech\VkTeamsBot\Test\Unit\Api;

use BelkaTech\VkTeamsBot\Api\FilesApi;
use BelkaTech\VkTeamsBot\Test\Spy\HttpClientSpy;
use PHPUnit\Framework\TestCase;

final class FilesApiTest extends TestCase
{
    private HttpClientSpy $httpClientSpy;

    private FilesApi $api;

    protected function setUp(): void
    {
        $this->httpClientSpy = new HttpClientSpy(
            getResponse: [
                'ok' => true,
                'type' => 'application/pdf',
                'size' => 12345,
                'filename' => 'doc.pdf',
                'url' => 'https://example.com/doc.pdf',
            ],
        );
        $this->api = new FilesApi($this->httpClientSpy);
    }

    public function testGetInfo(): void
    {
        // WHEN: getInfo is called
        $this->api->getInfo('file123');

        // THEN: correct path and fileId are sent
        [$method, $path, $params] = $this->httpClientSpy->calls[0];
        $this->assertSame('get', $method);
        $this->assertSame('/v1/files/getInfo', $path);
        $this->assertSame('file123', $params['fileId']);
    }
}
