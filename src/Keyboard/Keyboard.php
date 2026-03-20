<?php

declare(strict_types=1);

namespace BelkaTech\VkTeamsBot\Keyboard;

final class Keyboard implements \JsonSerializable
{
    /** @var list<list<Button>> */
    public array $rows = [];

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
     * @return list<list<Button>>
     */
    public function jsonSerialize(): array
    {
        return $this->rows;
    }
}
