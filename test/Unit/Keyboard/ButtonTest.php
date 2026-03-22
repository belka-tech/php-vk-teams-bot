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
        // GIVEN: a button with text only
        $button = new Button('Click me');

        // WHEN: serialized
        $result = $button->jsonSerialize();

        // THEN: only text and default style are present
        $this->assertSame('Click me', $result['text']);
        $this->assertSame('base', $result['style']);
        $this->assertArrayNotHasKey('url', $result);
        $this->assertArrayNotHasKey('callbackData', $result);
    }

    public function testSerializeWithUrl(): void
    {
        // GIVEN: a button with url
        $button = new Button('Open', url: 'https://example.com');

        // WHEN: serialized
        $result = $button->jsonSerialize();

        // THEN: url is present, callbackData is absent
        $this->assertSame('Open', $result['text']);
        $this->assertSame('https://example.com', $result['url']);
        $this->assertArrayNotHasKey('callbackData', $result);
    }

    public function testSerializeWithCallbackData(): void
    {
        // GIVEN: a button with callbackData
        $button = new Button('Action', callbackData: 'btn_confirm');

        // WHEN: serialized
        $result = $button->jsonSerialize();

        // THEN: callbackData is present, url is absent
        $this->assertSame('Action', $result['text']);
        $this->assertSame('btn_confirm', $result['callbackData']);
        $this->assertArrayNotHasKey('url', $result);
    }

    public function testSerializeWithStyle(): void
    {
        // GIVEN: a button with custom style
        $button = new Button('Danger', style: ButtonStyleEnum::Attention);

        // WHEN: serialized
        $result = $button->jsonSerialize();

        // THEN: style value is used
        $this->assertSame('attention', $result['style']);
    }

    public function testSerializeWithAllParams(): void
    {
        // GIVEN: a button with all params
        $button = new Button(
            'Full',
            url: 'https://example.com',
            callbackData: 'cb_data',
            style: ButtonStyleEnum::Primary,
        );

        // WHEN: serialized
        $result = $button->jsonSerialize();

        // THEN: all fields are present
        $this->assertSame([
            'text' => 'Full',
            'url' => 'https://example.com',
            'callbackData' => 'cb_data',
            'style' => 'primary',
        ], $result);
    }

    public function testJsonEncodeProducesValidJson(): void
    {
        // GIVEN: a button with url
        $button = new Button('Test', url: 'https://example.com');

        // WHEN: JSON-encoded
        $json = json_encode($button);

        // THEN: valid JSON with correct text
        $this->assertJson($json);
        $decoded = json_decode($json, true);
        $this->assertSame('Test', $decoded['text']);
    }
}
