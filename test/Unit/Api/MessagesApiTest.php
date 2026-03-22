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
        // WHEN: sendText is called
        $this->api->sendText('chat1', 'hello');

        // THEN: correct GET request is sent with parseMode
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
        // WHEN: sendText is called with reply and forward params
        $this->api->sendText(
            'chat1',
            'hello',
            replyMsgId: 42,
            forwardChatId: 'chat2',
            forwardMsgId: [1, 2, 3],
        );

        // THEN: reply and forward params are included
        $params = $this->httpClientSpy->calls[0][2];
        $this->assertSame(42, $params['replyMsgId']);
        $this->assertSame('chat2', $params['forwardChatId']);
        $this->assertSame('[1,2,3]', $params['forwardMsgId']);
    }

    public function testSendTextPassesNullForOmittedOptionalParams(): void
    {
        // WHEN: sendText is called without optional params
        $this->api->sendText('chat1', 'hello');

        // THEN: optional params are null
        $params = $this->httpClientSpy->calls[0][2];
        $this->assertNull($params['replyMsgId']);
        $this->assertNull($params['forwardChatId']);
        $this->assertNull($params['forwardMsgId']);
        $this->assertNull($params['inlineKeyboardMarkup']);
    }

    public function testNullForwardMsgIdIsNotEncodedAsJsonNullString(): void
    {
        // WHEN: sendText is called without forwardMsgId
        $this->api->sendText('chat1', 'hello');

        // THEN: forwardMsgId is null, not string "null"
        $params = $this->httpClientSpy->calls[0][2];
        $this->assertNull($params['forwardMsgId']);
        $this->assertNotSame('null', $params['forwardMsgId']);
    }

    public function testNullInlineKeyboardIsNotEncodedAsJsonNullString(): void
    {
        // WHEN: sendText is called without keyboard
        $this->api->sendText('chat1', 'hello');

        // THEN: inlineKeyboardMarkup is null, not string "null"
        $params = $this->httpClientSpy->calls[0][2];
        $this->assertNull($params['inlineKeyboardMarkup']);
        $this->assertNotSame('null', $params['inlineKeyboardMarkup']);
    }

    /**
     * @return iterable<string, array{?string, ?string}>
     */
    public static function invalidFileParamsProvider(): iterable
    {
        yield 'both null' => [null, null];
        yield 'both provided' => ['abc', '/tmp/file.txt'];
    }

    #[DataProvider('invalidFileParamsProvider')]
    public function testSendFileThrowsOnInvalidFileParams(?string $fileId, ?string $filePath): void
    {
        // THEN: exception is expected
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Exactly one of fileId or filePath must be provided');

        // WHEN: sendFile is called with invalid file param combination
        $this->api->sendFile(chatId: 'chat1', fileId: $fileId, filePath: $filePath);
    }

    public function testSendFileWithFileIdUsesGet(): void
    {
        // WHEN: sendFile is called with fileId
        $this->api->sendFile(chatId: 'chat1', fileId: 'abc');

        // THEN: GET request is used
        [$method, $path, $params] = $this->httpClientSpy->calls[0];
        $this->assertSame('get', $method);
        $this->assertSame('/v1/messages/sendFile', $path);
        $this->assertSame('abc', $params['fileId']);
    }

    public function testSendFileWithFilePathUsesPostMultipart(): void
    {
        // WHEN: sendFile is called with filePath
        $this->api->sendFile(chatId: 'chat1', filePath: '/tmp/test.txt');

        // THEN: multipart POST is used
        [$method, $path, $params, $filePath] = $this->httpClientSpy->calls[0];
        $this->assertSame('postMultipart', $method);
        $this->assertSame('/v1/messages/sendFile', $path);
        $this->assertSame('/tmp/test.txt', $filePath);
        $this->assertArrayNotHasKey('fileId', $params);
    }

    public function testSendFileWithCaption(): void
    {
        // WHEN: sendFile is called with caption
        $this->api->sendFile(chatId: 'chat1', fileId: 'abc', caption: 'my file');

        // THEN: caption param is included
        $params = $this->httpClientSpy->calls[0][2];
        $this->assertSame('my file', $params['caption']);
    }

    #[DataProvider('invalidFileParamsProvider')]
    public function testSendVoiceThrowsOnInvalidFileParams(?string $fileId, ?string $filePath): void
    {
        // THEN: exception is expected
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Exactly one of fileId or filePath must be provided');

        // WHEN: sendVoice is called with invalid file param combination
        $this->api->sendVoice(chatId: 'chat1', fileId: $fileId, filePath: $filePath);
    }

    public function testSendVoiceWithFileIdUsesGet(): void
    {
        // WHEN: sendVoice is called with fileId
        $this->api->sendVoice(chatId: 'chat1', fileId: 'voice1');

        // THEN: GET request is used
        [$method, $path, $params] = $this->httpClientSpy->calls[0];
        $this->assertSame('get', $method);
        $this->assertSame('/v1/messages/sendVoice', $path);
        $this->assertSame('voice1', $params['fileId']);
    }

    public function testSendVoiceWithFilePathUsesPostMultipart(): void
    {
        // WHEN: sendVoice is called with filePath
        $this->api->sendVoice(chatId: 'chat1', filePath: '/tmp/voice.ogg');

        // THEN: multipart POST is used
        [$method, $path, $params, $filePath] = $this->httpClientSpy->calls[0];
        $this->assertSame('postMultipart', $method);
        $this->assertSame('/v1/messages/sendVoice', $path);
        $this->assertSame('/tmp/voice.ogg', $filePath);
        $this->assertArrayNotHasKey('fileId', $params);
    }

    public function testSendVoiceWithForwardAndKeyboard(): void
    {
        // GIVEN: a keyboard with one button
        $keyboard = new Keyboard();
        $keyboard->addButton(new Button('btn', callbackData: 'cb1'));

        // WHEN: sendVoice is called with forward and keyboard params
        $this->api->sendVoice(
            chatId: 'chat1',
            fileId: 'voice1',
            replyMsgId: 10,
            forwardChatId: 'chat2',
            forwardMsgId: [5, 6],
            inlineKeyboardMarkup: $keyboard,
        );

        // THEN: all params are correctly sent
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
        // WHEN: sendVoice is called without optional params
        $this->api->sendVoice(chatId: 'chat1', fileId: 'voice1');

        // THEN: optional params are null, not string "null"
        $params = $this->httpClientSpy->calls[0][2];
        $this->assertNull($params['forwardMsgId']);
        $this->assertNull($params['inlineKeyboardMarkup']);
        $this->assertNotSame('null', $params['forwardMsgId']);
        $this->assertNotSame('null', $params['inlineKeyboardMarkup']);
    }

    public function testEditText(): void
    {
        // WHEN: editText is called
        $this->api->editText('chat1', 99, 'updated');

        // THEN: correct request is sent
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
        // GIVEN: a keyboard
        $keyboard = new Keyboard();
        $keyboard->addButton(new Button('ok', callbackData: 'ok'));

        // WHEN: editText is called with keyboard
        $this->api->editText('chat1', 99, 'updated', inlineKeyboardMarkup: $keyboard);

        // THEN: keyboard is serialized in params
        $params = $this->httpClientSpy->calls[0][2];
        $this->assertSame(
            json_encode($keyboard, JSON_THROW_ON_ERROR),
            $params['inlineKeyboardMarkup'],
        );
    }

    public function testEditTextPassesNullForOmittedKeyboard(): void
    {
        // WHEN: editText is called without keyboard
        $this->api->editText('chat1', 99, 'updated');

        // THEN: inlineKeyboardMarkup is null, not string "null"
        $params = $this->httpClientSpy->calls[0][2];
        $this->assertNull($params['inlineKeyboardMarkup']);
        $this->assertNotSame('null', $params['inlineKeyboardMarkup']);
    }

    public function testDeleteMessages(): void
    {
        // WHEN: deleteMessages is called
        $this->api->deleteMessages('chat1', [1, 2, 3]);

        // THEN: msgIds are JSON-encoded
        [$method, $path, $params] = $this->httpClientSpy->calls[0];
        $this->assertSame('get', $method);
        $this->assertSame('/v1/messages/deleteMessages', $path);
        $this->assertSame('[1,2,3]', $params['msgIds']);
    }

    public function testAnswerCallbackQuery(): void
    {
        // WHEN: answerCallbackQuery is called with text and showAlert
        $this->api->answerCallbackQuery('q1', text: 'done', showAlert: true);

        // THEN: correct params are sent
        [$method, $path, $params] = $this->httpClientSpy->calls[0];
        $this->assertSame('get', $method);
        $this->assertSame('/v1/messages/answerCallbackQuery', $path);
        $this->assertSame('q1', $params['queryId']);
        $this->assertSame('done', $params['textAnswer']);
        $this->assertTrue($params['showAlert']);
    }

    public function testAnswerCallbackQueryDefaults(): void
    {
        // WHEN: answerCallbackQuery is called with defaults
        $this->api->answerCallbackQuery('q1');

        // THEN: default values are used
        $params = $this->httpClientSpy->calls[0][2];
        $this->assertNull($params['textAnswer']);
        $this->assertFalse($params['showAlert']);
        $this->assertNull($params['url']);
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
        // WHEN: sendText is called with given parseMode
        $this->api->sendText('chat1', 'hello', parseMode: $parseMode);

        // THEN: correct parseMode is sent
        $this->assertSame($expected, $this->httpClientSpy->calls[0][2]['parseMode']);
    }

    #[DataProvider('parseModeProvider')]
    public function testSendFileParseMode(
        ?ParseModeEnum $parseMode,
        string $expected,
    ): void {
        // WHEN: sendFile is called with given parseMode
        $this->api->sendFile(chatId: 'chat1', fileId: 'f1', parseMode: $parseMode);

        // THEN: correct parseMode is sent
        $this->assertSame($expected, $this->httpClientSpy->calls[0][2]['parseMode']);
    }

    #[DataProvider('parseModeProvider')]
    public function testEditTextParseMode(
        ?ParseModeEnum $parseMode,
        string $expected,
    ): void {
        // WHEN: editText is called with given parseMode
        $this->api->editText('chat1', 1, 'text', parseMode: $parseMode);

        // THEN: correct parseMode is sent
        $this->assertSame($expected, $this->httpClientSpy->calls[0][2]['parseMode']);
    }

    /**
     * @return iterable<string, array{Keyboard}>
     */
    public static function keyboardProvider(): iterable
    {
        $keyboard = new Keyboard();
        $keyboard->addButton(new Button('btn', callbackData: 'cb'));
        yield 'Keyboard object' => [$keyboard];

        yield 'Keyboard::fromArray' => [Keyboard::fromArray([[['text' => 'btn', 'callbackData' => 'cb']]])];
    }

    #[DataProvider('keyboardProvider')]
    public function testSendTextKeyboardSerialization(
        Keyboard $keyboard,
    ): void {
        // WHEN: sendText is called with keyboard
        $this->api->sendText('chat1', 'hello', inlineKeyboardMarkup: $keyboard);

        // THEN: keyboard is JSON-encoded in params
        $this->assertSame(
            json_encode($keyboard, JSON_THROW_ON_ERROR),
            $this->httpClientSpy->calls[0][2]['inlineKeyboardMarkup'],
        );
    }

    #[DataProvider('keyboardProvider')]
    public function testSendFileKeyboardSerialization(
        Keyboard $keyboard,
    ): void {
        // WHEN: sendFile is called with keyboard
        $this->api->sendFile(chatId: 'chat1', fileId: 'f1', inlineKeyboardMarkup: $keyboard);

        // THEN: keyboard is JSON-encoded in params
        $this->assertSame(
            json_encode($keyboard, JSON_THROW_ON_ERROR),
            $this->httpClientSpy->calls[0][2]['inlineKeyboardMarkup'],
        );
    }

    #[DataProvider('keyboardProvider')]
    public function testSendVoiceKeyboardSerialization(
        Keyboard $keyboard,
    ): void {
        // WHEN: sendVoice is called with keyboard
        $this->api->sendVoice(chatId: 'chat1', fileId: 'v1', inlineKeyboardMarkup: $keyboard);

        // THEN: keyboard is JSON-encoded in params
        $this->assertSame(
            json_encode($keyboard, JSON_THROW_ON_ERROR),
            $this->httpClientSpy->calls[0][2]['inlineKeyboardMarkup'],
        );
    }

    #[DataProvider('keyboardProvider')]
    public function testEditTextKeyboardSerialization(
        Keyboard $keyboard,
    ): void {
        // WHEN: editText is called with keyboard
        $this->api->editText('chat1', 1, 'text', inlineKeyboardMarkup: $keyboard);

        // THEN: keyboard is JSON-encoded in params
        $this->assertSame(
            json_encode($keyboard, JSON_THROW_ON_ERROR),
            $this->httpClientSpy->calls[0][2]['inlineKeyboardMarkup'],
        );
    }
}
