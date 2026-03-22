<?php

declare(strict_types=1);

namespace BelkaTech\VkTeamsBot\Api;

use BelkaTech\VkTeamsBot\Enum\ParseModeEnum;
use BelkaTech\VkTeamsBot\Http\HttpClient;
use BelkaTech\VkTeamsBot\Keyboard\Keyboard;
use Psr\Http\Client\ClientExceptionInterface;

final readonly class MessagesApi
{
    public function __construct(
        private HttpClient $httpClient,
        private ParseModeEnum $parseMode,
    ) {}

    /**
     * @param list<string|int>|null $forwardMsgId
     * @return array{
     *     ok: bool,
     *     msgId: string,
     * }
     *
     * @throws ClientExceptionInterface
     */
    public function sendText(
        string $chatId,
        string $text,
        string|int|null $replyMsgId = null,
        ?string $forwardChatId = null,
        ?array $forwardMsgId = null,
        ?Keyboard $inlineKeyboardMarkup = null,
        ?object $format = null,
        ?ParseModeEnum $parseMode = null,
    ): array {
        /** @phpstan-ignore return.type */
        return $this->httpClient->get('/v1/messages/sendText', [
            'chatId' => $chatId,
            'text' => $text,
            'replyMsgId' => $replyMsgId,
            'forwardChatId' => $forwardChatId,
            'forwardMsgId' => $forwardMsgId !== null ? json_encode($forwardMsgId, flags: JSON_THROW_ON_ERROR) : null,
            'inlineKeyboardMarkup' => $inlineKeyboardMarkup !== null ? json_encode($inlineKeyboardMarkup, flags: JSON_THROW_ON_ERROR) : null,
            'format' => $format,
            'parseMode' => ($parseMode ?? $this->parseMode)->value,
        ]);
    }

    /**
     * @param list<string|int>|null $forwardMsgId
     * @return array{
     *     ok: bool,
     *     msgId: string,
     *     fileId?: string,
     * }
     *
     * @throws ClientExceptionInterface
     */
    public function sendFile(
        string $chatId,
        ?string $fileId = null,
        ?string $filePath = null,
        ?string $caption = null,
        string|int|null $replyMsgId = null,
        ?string $forwardChatId = null,
        ?array $forwardMsgId = null,
        ?Keyboard $inlineKeyboardMarkup = null,
        ?object $format = null,
        ?ParseModeEnum $parseMode = null,
    ): array {
        return $this->sendMedia(
            '/v1/messages/sendFile',
            $chatId,
            $fileId,
            $filePath,
            $caption,
            $replyMsgId,
            $forwardChatId,
            $forwardMsgId,
            $inlineKeyboardMarkup,
            $format,
            $parseMode,
        );
    }

    /**
     * @param list<string|int>|null $forwardMsgId
     * @return array{
     *     ok: bool,
     *     msgId: string,
     *     fileId?: string,
     * }
     *
     * @throws ClientExceptionInterface
     */
    public function sendVoice(
        string $chatId,
        ?string $fileId = null,
        ?string $filePath = null,
        string|int|null $replyMsgId = null,
        ?string $forwardChatId = null,
        ?array $forwardMsgId = null,
        ?Keyboard $inlineKeyboardMarkup = null,
    ): array {
        if (!($fileId === null xor $filePath === null)) {
            throw new \InvalidArgumentException('Exactly one of fileId or filePath must be provided');
        }

        $params = [
            'chatId' => $chatId,
            'fileId' => $fileId,
            'replyMsgId' => $replyMsgId,
            'forwardChatId' => $forwardChatId,
            'forwardMsgId' => $forwardMsgId !== null ? json_encode($forwardMsgId, flags: JSON_THROW_ON_ERROR) : null,
            'inlineKeyboardMarkup' => $inlineKeyboardMarkup !== null ? json_encode($inlineKeyboardMarkup, flags: JSON_THROW_ON_ERROR) : null,
        ];

        if ($filePath !== null) {
            unset($params['fileId']);

            /** @phpstan-ignore return.type */
            return $this->httpClient->postMultipart(
                '/v1/messages/sendVoice',
                $params,
                $filePath,
            );
        }

        /** @phpstan-ignore return.type */
        return $this->httpClient->get('/v1/messages/sendVoice', $params);
    }

    /**
     * @return array{ok: bool}
     *
     * @throws ClientExceptionInterface
     */
    public function editText(
        string $chatId,
        string|int $msgId,
        string $text,
        ?Keyboard $inlineKeyboardMarkup = null,
        ?object $format = null,
        ?ParseModeEnum $parseMode = null,
    ): array {
        /** @phpstan-ignore return.type */
        return $this->httpClient->get('/v1/messages/editText', [
            'chatId' => $chatId,
            'msgId' => $msgId,
            'text' => $text,
            'inlineKeyboardMarkup' => $inlineKeyboardMarkup !== null ? json_encode($inlineKeyboardMarkup, flags: JSON_THROW_ON_ERROR) : null,
            'format' => $format,
            'parseMode' => ($parseMode ?? $this->parseMode)->value,
        ]);
    }

    /**
     * @param list<string|int> $msgIds
     * @return array{ok: bool}
     *
     * @throws ClientExceptionInterface
     */
    public function deleteMessages(
        string $chatId,
        array $msgIds,
    ): array {
        /** @phpstan-ignore return.type */
        return $this->httpClient->get('/v1/messages/deleteMessages', [
            'chatId' => $chatId,
            'msgIds' => json_encode($msgIds, flags: JSON_THROW_ON_ERROR),
        ]);
    }

    /**
     * @return array{ok: bool}
     *
     * @throws ClientExceptionInterface
     */
    public function answerCallbackQuery(
        string $queryId,
        ?string $text = null,
        bool $showAlert = false,
        ?string $url = null,
    ): array {
        /** @phpstan-ignore return.type */
        return $this->httpClient->get('/v1/messages/answerCallbackQuery', [
            'queryId' => $queryId,
            'textAnswer' => $text,
            'showAlert' => $showAlert,
            'url' => $url,
        ]);
    }



    /**
     * @param list<string|int>|null $forwardMsgId
     * @return array{
     *     ok: bool,
     *     msgId: string,
     *     fileId?: string,
     * }
     *
     * @throws ClientExceptionInterface
     */
    private function sendMedia(
        string $endpoint,
        string $chatId,
        ?string $fileId,
        ?string $filePath,
        ?string $caption,
        string|int|null $replyMsgId,
        ?string $forwardChatId,
        ?array $forwardMsgId,
        ?Keyboard $inlineKeyboardMarkup,
        ?object $format,
        ?ParseModeEnum $parseMode,
    ): array {
        if (!($fileId === null xor $filePath === null)) {
            throw new \InvalidArgumentException('Exactly one of fileId or filePath must be provided');
        }

        $params = [
            'chatId' => $chatId,
            'caption' => $caption,
            'replyMsgId' => $replyMsgId,
            'forwardChatId' => $forwardChatId,
            'forwardMsgId' => $forwardMsgId !== null ? json_encode($forwardMsgId, flags: JSON_THROW_ON_ERROR) : null,
            'inlineKeyboardMarkup' => $inlineKeyboardMarkup !== null ? json_encode($inlineKeyboardMarkup, flags: JSON_THROW_ON_ERROR) : null,
            'format' => $format,
            'parseMode' => ($parseMode ?? $this->parseMode)->value,
        ];

        if ($filePath !== null) {
            /** @phpstan-ignore return.type */
            return $this->httpClient->postMultipart(
                $endpoint,
                $params,
                $filePath,
            );
        }

        $params['fileId'] = $fileId;

        /** @phpstan-ignore return.type */
        return $this->httpClient->get(
            $endpoint,
            $params,
        );
    }
}
