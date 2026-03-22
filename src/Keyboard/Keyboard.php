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
        $keyboard = new self();

        foreach ($rows as $row) {
            $buttons = [];
            foreach ($row as $buttonData) {
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
