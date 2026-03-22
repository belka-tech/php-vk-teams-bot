<?php

declare(strict_types=1);

namespace BelkaTech\VkTeamsBot\Keyboard;

use BelkaTech\VkTeamsBot\Enum\ButtonStyleEnum;

final class Keyboard implements \JsonSerializable
{
    /** @var list<list<Button>> */
    private array $rows = [];

    /**
     * @param list<Button> $row
     */
    public function addRow(
        array $row,
    ): void {
        $this->rows[] = $row;
    }

    public function addButton(
        Button $button,
    ): void {
        $this->rows[] = [$button];
    }

    /**
     * @param list<list<array{
     *     text: string,
     *     url?: string,
     *     callbackData?: string,
     *     style?: string,
     * }>> $rows
     */
    public static function fromArray(
        array $rows,
    ): self {
        if ($rows === []) {
            throw new \InvalidArgumentException('Keyboard rows must not be empty');
        }

        $keyboard = new self();

        foreach ($rows as $rowIndex => $row) {
            if (!\is_array($row) || $row === []) {
                throw new \InvalidArgumentException("Row #{$rowIndex} must be a non-empty array of buttons");
            }

            $buttons = [];
            foreach ($row as $buttonIndex => $buttonData) {
                if (!\is_array($buttonData)) {
                    throw new \InvalidArgumentException("Button #{$buttonIndex} in row #{$rowIndex} must be an array");
                }

                if (!isset($buttonData['text']) || !\is_string($buttonData['text'])) {
                    throw new \InvalidArgumentException("Button #{$buttonIndex} in row #{$rowIndex} must have a string 'text' key");
                }

                $buttons[] = new Button(
                    text: $buttonData['text'],
                    url: $buttonData['url'] ?? null,
                    callbackData: $buttonData['callbackData'] ?? null,
                    style: isset($buttonData['style']) ? ButtonStyleEnum::from($buttonData['style']) : ButtonStyleEnum::Base,
                );
            }
            $keyboard->addRow($buttons);
        }

        return $keyboard;
    }

    /**
     * @return list<list<Button>>
     */
    public function jsonSerialize(): array
    {
        return $this->rows;
    }
}
