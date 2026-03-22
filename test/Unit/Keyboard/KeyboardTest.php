<?php

declare(strict_types=1);

namespace BelkaTech\VkTeamsBot\Test\Unit\Keyboard;

use BelkaTech\VkTeamsBot\Keyboard\Button;
use BelkaTech\VkTeamsBot\Keyboard\Keyboard;
use PHPUnit\Framework\TestCase;

final class KeyboardTest extends TestCase
{
    public function testEmptyKeyboard(): void
    {
        $keyboard = new Keyboard();

        $this->assertSame([], $keyboard->jsonSerialize());
    }

    public function testAddButton(): void
    {
        $keyboard = new Keyboard();
        $button = new Button('Click');

        $keyboard->addButton($button);

        $rows = $keyboard->jsonSerialize();
        $this->assertCount(1, $rows);
        $this->assertCount(1, $rows[0]);
        $this->assertSame($button, $rows[0][0]);
    }

    public function testAddRow(): void
    {
        $keyboard = new Keyboard();
        $btn1 = new Button('One');
        $btn2 = new Button('Two');

        $keyboard->addRow([$btn1, $btn2]);

        $rows = $keyboard->jsonSerialize();
        $this->assertCount(1, $rows);
        $this->assertCount(2, $rows[0]);
    }

    public function testMultipleRows(): void
    {
        $keyboard = new Keyboard();
        $keyboard->addButton(new Button('Solo'));
        $keyboard->addRow([new Button('A'), new Button('B')]);
        $keyboard->addButton(new Button('Last'));

        $rows = $keyboard->jsonSerialize();
        $this->assertCount(3, $rows);
        $this->assertCount(1, $rows[0]);
        $this->assertCount(2, $rows[1]);
        $this->assertCount(1, $rows[2]);
    }

    public function testFromArray(): void
    {
        $keyboard = Keyboard::fromArray([
            [
                ['text' => 'A', 'url' => 'https://example.com'],
                ['text' => 'B', 'callbackData' => 'cb1'],
            ],
            [
                ['text' => 'C', 'style' => 'primary'],
            ],
        ]);

        $rows = $keyboard->jsonSerialize();
        $this->assertCount(2, $rows);
        $this->assertCount(2, $rows[0]);
        $this->assertCount(1, $rows[1]);

        $json = json_encode($keyboard, JSON_THROW_ON_ERROR);
        $this->assertStringContainsString('"text":"A"', $json);
        $this->assertStringContainsString('"url":"https:\/\/example.com"', $json);
        $this->assertStringContainsString('"callbackData":"cb1"', $json);
        $this->assertStringContainsString('"style":"primary"', $json);
    }

    public function testFromArrayMinimalButtons(): void
    {
        $keyboard = Keyboard::fromArray([
            [['text' => 'Only text']],
        ]);

        $rows = $keyboard->jsonSerialize();
        $this->assertCount(1, $rows);

        $json = json_encode($keyboard, JSON_THROW_ON_ERROR);
        $this->assertStringContainsString('"text":"Only text"', $json);
        $this->assertStringNotContainsString('"url"', $json);
        $this->assertStringNotContainsString('"callbackData"', $json);
    }

    public function testFromArrayEmptyRowsThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Keyboard rows must not be empty');
        Keyboard::fromArray([]);
    }

    public function testFromArrayEmptyRowThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Row #0 must be a non-empty array of buttons');
        Keyboard::fromArray([[]]);
    }

    public function testFromArrayInvalidButtonThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Button #0 in row #0 must be an array');
        Keyboard::fromArray([['not an array']]);
    }

    public function testFromArrayMissingTextThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("must have a string 'text' key");
        Keyboard::fromArray([[['callbackData' => 'cb']]]);
    }

    public function testFromArrayInvalidStyleThrows(): void
    {
        $this->expectException(\ValueError::class);
        Keyboard::fromArray([[['text' => 'btn', 'style' => 'invalid']]]);
    }

    public function testJsonEncodeProducesValidJson(): void
    {
        $keyboard = new Keyboard();
        $keyboard->addButton(new Button('Test'));

        $json = json_encode($keyboard);

        $this->assertJson($json);
        $decoded = json_decode($json, true);
        $this->assertCount(1, $decoded);
    }
}
