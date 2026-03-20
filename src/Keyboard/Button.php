<?php

declare(strict_types=1);

namespace BelkaTech\VkTeamsBot\Keyboard;

use BelkaTech\VkTeamsBot\Enum\ButtonStyleEnum;

final class Button implements \JsonSerializable
{
    /**
     * @param string $text Button label
     * @param string|null $url URL to open on click
     * @param string|null $callbackData Callback query to send to the bot
     * @param ButtonStyleEnum $style Button style (text color)
     */
    public function __construct(
        private readonly string $text,
        private readonly ?string $url = null,
        private readonly ?string $callbackData = null,
        private readonly ButtonStyleEnum $style = ButtonStyleEnum::Base,
    ) {}

    /**
     * @return array{
     *     text: string,
     *     url?: string,
     *     callbackData?: string,
     *     style?: string,
     * }
     */
    public function jsonSerialize(): array
    {
        $result = ['text' => $this->text];

        if ($this->url !== null && $this->url !== '') {
            $result['url'] = $this->url;
        }

        if ($this->callbackData !== null && $this->callbackData !== '') {
            $result['callbackData'] = $this->callbackData;
        }

        $result['style'] = $this->style->value;

        return $result;
    }
}
