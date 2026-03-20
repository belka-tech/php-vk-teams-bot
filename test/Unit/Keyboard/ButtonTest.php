<?php

declare(strict_types=1);

namespace BelkaTech\VkTeamsBot\Test\Unit\Keyboard;

use BelkaTech\VkTeamsBot\Enum\ButtonStyleEnum;
use BelkaTech\VkTeamsBot\Keyboard\Button;
use PHPUnit\Framework\TestCase;

final class ButtonTest extends TestCase
{
    public function testSerializeWithTextOnly(): void
    {
        $button = new Button('Click me');

        $result = $button->jsonSerialize();

        $this->assertSame('Click me', $result['text']);
        $this->assertSame('base', $result['style']);
        $this->assertArrayNotHasKey('url', $result);
        $this->assertArrayNotHasKey('callbackData', $result);
    }

    public function testSerializeWithUrl(): void
    {
        $button = new Button('Open', url: 'https://example.com');

        $result = $button->jsonSerialize();

        $this->assertSame('Open', $result['text']);
        $this->assertSame('https://example.com', $result['url']);
        $this->assertArrayNotHasKey('callbackData', $result);
    }

    public function testSerializeWithCallbackData(): void
    {
        $button = new Button('Action', callbackData: 'btn_confirm');

        $result = $button->jsonSerialize();

        $this->assertSame('Action', $result['text']);
        $this->assertSame('btn_confirm', $result['callbackData']);
        $this->assertArrayNotHasKey('url', $result);
    }

    public function testSerializeWithStyle(): void
    {
        $button = new Button('Danger', style: ButtonStyleEnum::Attention);

        $result = $button->jsonSerialize();

        $this->assertSame('attention', $result['style']);
    }

    public function testSerializeWithAllParams(): void
    {
        $button = new Button(
            'Full',
            url: 'https://example.com',
            callbackData: 'cb_data',
            style: ButtonStyleEnum::Primary,
        );

        $result = $button->jsonSerialize();

        $this->assertSame([
            'text' => 'Full',
            'url' => 'https://example.com',
            'callbackData' => 'cb_data',
            'style' => 'primary',
        ], $result);
    }

    public function testJsonEncodeProducesValidJson(): void
    {
        $button = new Button('Test', url: 'https://example.com');

        $json = json_encode($button);

        $this->assertJson($json);
        $decoded = json_decode($json, true);
        $this->assertSame('Test', $decoded['text']);
    }
}
