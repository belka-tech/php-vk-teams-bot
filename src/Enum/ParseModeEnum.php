<?php

declare(strict_types=1);

namespace BelkaTech\VkTeamsBot\Enum;

enum ParseModeEnum: string
{
    case MarkdownV2 = 'MarkdownV2';
    case Html = 'HTML';
}
