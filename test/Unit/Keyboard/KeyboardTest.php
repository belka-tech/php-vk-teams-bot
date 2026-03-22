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
        // WHEN: empty Keyboard is serialized
        $keyboard = new Keyboard();

        // THEN: empty array
        $this->assertSame([], $keyboard->jsonSerialize());
    }

    public function testAddButton(): void
    {
        // GIVEN: a keyboard and a button
        $keyboard = new Keyboard();
        $button = new Button('Click');

        // WHEN: button is added
        $keyboard->addButton($button);

        // THEN: one row with one button
        $rows = $keyboard->jsonSerialize();
        $this->assertCount(1, $rows);
        $this->assertCount(1, $rows[0]);
        $this->assertSame($button, $rows[0][0]);
    }

    public function testAddRow(): void
    {
        // GIVEN: a keyboard and two buttons
        $keyboard = new Keyboard();
        $btn1 = new Button('One');
        $btn2 = new Button('Two');

        // WHEN: a row with two buttons is added
        $keyboard->addRow([$btn1, $btn2]);

        // THEN: one row with two buttons
        $rows = $keyboard->jsonSerialize();
        $this->assertCount(1, $rows);
        $this->assertCount(2, $rows[0]);
    }

    public function testMultipleRows(): void
    {
        // GIVEN: a keyboard
        $keyboard = new Keyboard();

        // WHEN: buttons and rows are added in sequence
        $keyboard->addButton(new Button('Solo'));
        $keyboard->addRow([new Button('A'), new Button('B')]);
        $keyboard->addButton(new Button('Last'));

        // THEN: three rows with correct button counts
        $rows = $keyboard->jsonSerialize();
        $this->assertCount(3, $rows);
        $this->assertCount(1, $rows[0]);
        $this->assertCount(2, $rows[1]);
        $this->assertCount(1, $rows[2]);
    }

    public function testFromArray(): void
    {
        // WHEN: Keyboard is created from array
        $keyboard = Keyboard::fromArray([
            [
                ['text' => 'A', 'url' => 'https://example.com'],
                ['text' => 'B', 'callbackData' => 'cb1'],
            ],
            [
                ['text' => 'C', 'style' => 'primary'],
            ],
        ]);

        // THEN: correct structure and JSON output
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
        // WHEN: Keyboard is created with minimal button data
        $keyboard = Keyboard::fromArray([
            [['text' => 'Only text']],
        ]);

        // THEN: only text field is in JSON
        $rows = $keyboard->jsonSerialize();
        $this->assertCount(1, $rows);

        $json = json_encode($keyboard, JSON_THROW_ON_ERROR);
        $this->assertStringContainsString('"text":"Only text"', $json);
        $this->assertStringNotContainsString('"url"', $json);
        $this->assertStringNotContainsString('"callbackData"', $json);
    }

    public function testFromArrayEmptyRowsThrows(): void
    {
        // THEN: exception is expected
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Keyboard rows must not be empty');

        // WHEN: fromArray is called with empty array
        Keyboard::fromArray([]);
    }

    public function testFromArrayEmptyRowThrows(): void
    {
        // THEN: exception is expected
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Row #0 must be a non-empty array of buttons');

        // WHEN: fromArray is called with empty row
        Keyboard::fromArray([[]]);
    }

    public function testFromArrayInvalidButtonThrows(): void
    {
        // THEN: exception is expected
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Button #0 in row #0 must be an array');

        // WHEN: fromArray is called with non-array button
        Keyboard::fromArray([['not an array']]);
    }

    public function testFromArrayMissingTextThrows(): void
    {
        // THEN: exception is expected
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("must have a string 'text' key");

        // WHEN: fromArray is called with button missing text
        Keyboard::fromArray([[['callbackData' => 'cb']]]);
    }

    public function testFromArrayInvalidStyleThrows(): void
    {
        // THEN: ValueError is expected
        $this->expectException(\ValueError::class);

        // WHEN: fromArray is called with invalid style
        Keyboard::fromArray([[['text' => 'btn', 'style' => 'invalid']]]);
    }

    public function testJsonEncodeProducesValidJson(): void
    {
        // GIVEN: a keyboard with one button
        $keyboard = new Keyboard();
        $keyboard->addButton(new Button('Test'));

        // WHEN: JSON-encoded
        $json = json_encode($keyboard);

        // THEN: valid JSON with one row
        $this->assertJson($json);
        $decoded = json_decode($json, true);
        $this->assertCount(1, $decoded);
    }
}
