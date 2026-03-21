<?php

declare(strict_types=1);

namespace BelkaTech\VkTeamsBot\Test\Unit\Api;

use BelkaTech\VkTeamsBot\Api\MessagesApi;
use BelkaTech\VkTeamsBot\Enum\ParseModeEnum;
use BelkaTech\VkTeamsBot\Keyboard\Button;
use BelkaTech\VkTeamsBot\Keyboard\Keyboard;
use BelkaTech\VkTeamsBot\Test\Spy\HttpClientSpy;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class MessagesApiTest extends TestCase
{
    private HttpClientSpy $httpClientSpy;

    private MessagesApi $api;

    protected function setUp(): void
    {
        $this->httpClientSpy = new HttpClientSpy(
            getResponse: ['ok' => true, 'msgId' => '1'],
            postMultipartResponse: ['ok' => true, 'msgId' => '1'],
        );
        $this->api = new MessagesApi($this->httpClientSpy, ParseModeEnum::Html);
    }

    public function testSendText(): void
    {
        $this->api->sendText('chat1', 'hello');

        $this->assertCount(1, $this->httpClientSpy->calls);
        [$method, $path, $params] = $this->httpClientSpy->calls[0];
        $this->assertSame('get', $method);
        $this->assertSame('/v1/messages/sendText', $path);
        $this->assertSame('chat1', $params['chatId']);
        $this->assertSame('hello', $params['text']);
        $this->assertSame('HTML', $params['parseMode']);
    }

    public function testSendTextWithReplyAndForward(): void
    {
        $this->api->sendText(
            'chat1',
            'hello',
            replyMsgId: 42,
            forwardChatId: 'chat2',
            forwardMsgId: [1, 2, 3],
        );

        $params = $this->httpClientSpy->calls[0][2];
        $this->assertSame(42, $params['replyMsgId']);
        $this->assertSame('chat2', $params['forwardChatId']);
        $this->assertSame('[1,2,3]', $params['forwardMsgId']);
    }

    public function testSendTextPassesNullForOmittedOptionalParams(): void
    {
        $this->api->sendText('chat1', 'hello');

        $params = $this->httpClientSpy->calls[0][2];
        $this->assertNull($params['replyMsgId']);
        $this->assertNull($params['forwardChatId']);
        $this->assertNull($params['forwardMsgId']);
        $this->assertNull($params['inlineKeyboardMarkup']);
    }

    public function testNullForwardMsgIdIsNotEncodedAsJsonNullString(): void
    {
        $this->api->sendText('chat1', 'hello');

        $params = $this->httpClientSpy->calls[0][2];
        $this->assertNull($params['forwardMsgId']);
        $this->assertNotSame('null', $params['forwardMsgId']);
    }

    public function testNullInlineKeyboardIsNotEncodedAsJsonNullString(): void
    {
        $this->api->sendText('chat1', 'hello');

        $params = $this->httpClientSpy->calls[0][2];
        $this->assertNull($params['inlineKeyboardMarkup']);
        $this->assertNotSame('null', $params['inlineKeyboardMarkup']);
    }

    public function testSendFileWithFileIdUsesGet(): void
    {
        $this->api->sendFile(chatId: 'chat1', fileId: 'abc');

        [$method, $path, $params] = $this->httpClientSpy->calls[0];
        $this->assertSame('get', $method);
        $this->assertSame('/v1/messages/sendFile', $path);
        $this->assertSame('abc', $params['fileId']);
    }

    public function testSendFileWithFilePathUsesPostMultipart(): void
    {
        $this->api->sendFile(chatId: 'chat1', filePath: '/tmp/test.txt');

        [$method, $path, $params, $filePath] = $this->httpClientSpy->calls[0];
        $this->assertSame('postMultipart', $method);
        $this->assertSame('/v1/messages/sendFile', $path);
        $this->assertSame('/tmp/test.txt', $filePath);
        $this->assertArrayNotHasKey('fileId', $params);
    }

    public function testSendFileWithCaption(): void
    {
        $this->api->sendFile(chatId: 'chat1', fileId: 'abc', caption: 'my file');

        $params = $this->httpClientSpy->calls[0][2];
        $this->assertSame('my file', $params['caption']);
    }

    public function testSendVoiceWithFileIdUsesGet(): void
    {
        $this->api->sendVoice(chatId: 'chat1', fileId: 'voice1');

        [$method, $path, $params] = $this->httpClientSpy->calls[0];
        $this->assertSame('get', $method);
        $this->assertSame('/v1/messages/sendVoice', $path);
        $this->assertSame('voice1', $params['fileId']);
    }

    public function testSendVoiceWithFilePathUsesPostMultipart(): void
    {
        $this->api->sendVoice(chatId: 'chat1', filePath: '/tmp/voice.ogg');

        [$method, $path, $params, $filePath] = $this->httpClientSpy->calls[0];
        $this->assertSame('postMultipart', $method);
        $this->assertSame('/v1/messages/sendVoice', $path);
        $this->assertSame('/tmp/voice.ogg', $filePath);
        $this->assertArrayNotHasKey('fileId', $params);
    }

    public function testSendVoiceWithForwardAndKeyboard(): void
    {
        $keyboard = new Keyboard();
        $keyboard->addButton(new Button('btn', callbackData: 'cb1'));

        $this->api->sendVoice(
            chatId: 'chat1',
            fileId: 'voice1',
            replyMsgId: 10,
            forwardChatId: 'chat2',
            forwardMsgId: [5, 6],
            inlineKeyboardMarkup: $keyboard,
        );

        $params = $this->httpClientSpy->calls[0][2];
        $this->assertSame(10, $params['replyMsgId']);
        $this->assertSame('chat2', $params['forwardChatId']);
        $this->assertSame('[5,6]', $params['forwardMsgId']);
        $this->assertSame(
            json_encode($keyboard, JSON_THROW_ON_ERROR),
            $params['inlineKeyboardMarkup'],
        );
    }

    public function testSendVoicePassesNullForOmittedOptionalParams(): void
    {
        $this->api->sendVoice(chatId: 'chat1', fileId: 'voice1');

        $params = $this->httpClientSpy->calls[0][2];
        $this->assertNull($params['forwardMsgId']);
        $this->assertNull($params['inlineKeyboardMarkup']);
        $this->assertNotSame('null', $params['forwardMsgId']);
        $this->assertNotSame('null', $params['inlineKeyboardMarkup']);
    }

    public function testEditText(): void
    {
        $this->api->editText('chat1', 99, 'updated');

        [$method, $path, $params] = $this->httpClientSpy->calls[0];
        $this->assertSame('get', $method);
        $this->assertSame('/v1/messages/editText', $path);
        $this->assertSame('chat1', $params['chatId']);
        $this->assertSame(99, $params['msgId']);
        $this->assertSame('updated', $params['text']);
        $this->assertSame('HTML', $params['parseMode']);
    }

    public function testEditTextWithKeyboard(): void
    {
        $keyboard = new Keyboard();
        $keyboard->addButton(new Button('ok', callbackData: 'ok'));

        $this->api->editText('chat1', 99, 'updated', inlineKeyboardMarkup: $keyboard);

        $params = $this->httpClientSpy->calls[0][2];
        $this->assertSame(
            json_encode($keyboard, JSON_THROW_ON_ERROR),
            $params['inlineKeyboardMarkup'],
        );
    }

    public function testEditTextPassesNullForOmittedKeyboard(): void
    {
        $this->api->editText('chat1', 99, 'updated');

        $params = $this->httpClientSpy->calls[0][2];
        $this->assertNull($params['inlineKeyboardMarkup']);
        $this->assertNotSame('null', $params['inlineKeyboardMarkup']);
    }

    public function testDeleteMessages(): void
    {
        $this->api->deleteMessages('chat1', [1, 2, 3]);

        [$method, $path, $params] = $this->httpClientSpy->calls[0];
        $this->assertSame('get', $method);
        $this->assertSame('/v1/messages/deleteMessages', $path);
        $this->assertSame('[1,2,3]', $params['msgIds']);
    }

    public function testAnswerCallbackQuery(): void
    {
        $this->api->answerCallbackQuery('q1', text: 'done', showAlert: true);

        [$method, $path, $params] = $this->httpClientSpy->calls[0];
        $this->assertSame('get', $method);
        $this->assertSame('/v1/messages/answerCallbackQuery', $path);
        $this->assertSame('q1', $params['queryId']);
        $this->assertSame('done', $params['textAnswer']);
        $this->assertTrue($params['showAlert']);
    }

    public function testAnswerCallbackQueryDefaults(): void
    {
        $this->api->answerCallbackQuery('q1');

        $params = $this->httpClientSpy->calls[0][2];
        $this->assertNull($params['textAnswer']);
        $this->assertFalse($params['showAlert']);
        $this->assertNull($params['url']);
    }

    public function testPinMessage(): void
    {
        $this->api->pinMessage('group1', 42);

        [$method, $path, $params] = $this->httpClientSpy->calls[0];
        $this->assertSame('get', $method);
        $this->assertSame('/v1/chats/pinMessage', $path);
        $this->assertSame('group1', $params['groupOrChannelId']);
        $this->assertSame(42, $params['msgId']);
    }

    public function testUnpinMessage(): void
    {
        $this->api->unpinMessage('group1', 42);

        $this->assertSame('/v1/chats/unpinMessage', $this->httpClientSpy->calls[0][1]);
    }

    public function testFilesGetInfo(): void
    {
        $this->api->filesGetInfo('file123');

        [$method, $path, $params] = $this->httpClientSpy->calls[0];
        $this->assertSame('get', $method);
        $this->assertSame('/v1/files/getInfo', $path);
        $this->assertSame('file123', $params['fileId']);
    }

    // --- Data providers ---

    /**
     * @return iterable<string, array{ParseModeEnum|null, string}>
     */
    public static function parseModeProvider(): iterable
    {
        yield 'default HTML' => [null, 'HTML'];
        yield 'override MarkdownV2' => [ParseModeEnum::MarkdownV2, 'MarkdownV2'];
        yield 'explicit HTML' => [ParseModeEnum::Html, 'HTML'];
    }

    #[DataProvider('parseModeProvider')]
    public function testSendTextParseMode(
        ?ParseModeEnum $parseMode,
        string $expected,
    ): void {
        $this->api->sendText('chat1', 'hello', parseMode: $parseMode);

        $this->assertSame($expected, $this->httpClientSpy->calls[0][2]['parseMode']);
    }

    #[DataProvider('parseModeProvider')]
    public function testSendFileParseMode(
        ?ParseModeEnum $parseMode,
        string $expected,
    ): void {
        $this->api->sendFile(chatId: 'chat1', fileId: 'f1', parseMode: $parseMode);

        $this->assertSame($expected, $this->httpClientSpy->calls[0][2]['parseMode']);
    }

    #[DataProvider('parseModeProvider')]
    public function testEditTextParseMode(
        ?ParseModeEnum $parseMode,
        string $expected,
    ): void {
        $this->api->editText('chat1', 1, 'text', parseMode: $parseMode);

        $this->assertSame($expected, $this->httpClientSpy->calls[0][2]['parseMode']);
    }

    /**
     * @return iterable<string, array{Keyboard|list<list<array{text: string, callbackData?: string}>>}>
     */
    public static function keyboardProvider(): iterable
    {
        $keyboard = new Keyboard();
        $keyboard->addButton(new Button('btn', callbackData: 'cb'));
        yield 'Keyboard object' => [$keyboard];

        yield 'raw array' => [[[['text' => 'btn', 'callbackData' => 'cb']]]];
    }

    #[DataProvider('keyboardProvider')]
    public function testSendTextKeyboardSerialization(
        Keyboard|array $keyboard,
    ): void {
        $this->api->sendText('chat1', 'hello', inlineKeyboardMarkup: $keyboard);

        $this->assertSame(
            json_encode($keyboard, JSON_THROW_ON_ERROR),
            $this->httpClientSpy->calls[0][2]['inlineKeyboardMarkup'],
        );
    }

    #[DataProvider('keyboardProvider')]
    public function testSendFileKeyboardSerialization(
        Keyboard|array $keyboard,
    ): void {
        $this->api->sendFile(chatId: 'chat1', fileId: 'f1', inlineKeyboardMarkup: $keyboard);

        $this->assertSame(
            json_encode($keyboard, JSON_THROW_ON_ERROR),
            $this->httpClientSpy->calls[0][2]['inlineKeyboardMarkup'],
        );
    }

    #[DataProvider('keyboardProvider')]
    public function testSendVoiceKeyboardSerialization(
        Keyboard|array $keyboard,
    ): void {
        $this->api->sendVoice(chatId: 'chat1', fileId: 'v1', inlineKeyboardMarkup: $keyboard);

        $this->assertSame(
            json_encode($keyboard, JSON_THROW_ON_ERROR),
            $this->httpClientSpy->calls[0][2]['inlineKeyboardMarkup'],
        );
    }

    #[DataProvider('keyboardProvider')]
    public function testEditTextKeyboardSerialization(
        Keyboard|array $keyboard,
    ): void {
        $this->api->editText('chat1', 1, 'text', inlineKeyboardMarkup: $keyboard);

        $this->assertSame(
            json_encode($keyboard, JSON_THROW_ON_ERROR),
            $this->httpClientSpy->calls[0][2]['inlineKeyboardMarkup'],
        );
    }
}
