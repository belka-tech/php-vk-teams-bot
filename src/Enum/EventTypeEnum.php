<?php

declare(strict_types=1);

namespace BelkaTech\VkTeamsBot\Enum;

enum EventTypeEnum: string
{
    case MessageNew = 'newMessage';
    case MessageEdited = 'editedMessage';
    case MessageDeleted = 'deletedMessage';
    case MessagePinned = 'pinnedMessage';
    case MessageUnpinned = 'unpinnedMessage';
    case ChatMemberJoined = 'newChatMembers';
    case ChatMemberLeft = 'leftChatMembers';
    case CallbackQuery = 'callbackQuery';
}
