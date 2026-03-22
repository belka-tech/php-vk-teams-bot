<?php

declare(strict_types=1);

namespace BelkaTech\VkTeamsBot\Test\Unit\Api;

use BelkaTech\VkTeamsBot\Api\ChatsApi;
use BelkaTech\VkTeamsBot\Test\Spy\HttpClientSpy;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ChatsApiTest extends TestCase
{
    private HttpClientSpy $httpClientSpy;

    private ChatsApi $api;

    protected function setUp(): void
    {
        $this->httpClientSpy = new HttpClientSpy(
            getResponse: ['ok' => true, 'sn' => 'chat1'],
        );
        $this->api = new ChatsApi($this->httpClientSpy);
    }

    public function testCreate(): void
    {
        $members = [['sn' => 'user1'], ['sn' => 'user2']];

        $this->api->create('Test Chat', about: 'desc', members: $members, public: true);

        [$method, $path, $params] = $this->httpClientSpy->calls[0];
        $this->assertSame('get', $method);
        $this->assertSame('/v1/chats/createChat', $path);
        $this->assertSame('Test Chat', $params['name']);
        $this->assertSame('desc', $params['about']);
        $this->assertSame(json_encode($members, JSON_THROW_ON_ERROR), $params['members']);
        $this->assertTrue($params['public']);
    }

    public function testCreateDefaults(): void
    {
        $this->api->create('Chat');

        $params = $this->httpClientSpy->calls[0][2];
        $this->assertNull($params['about']);
        $this->assertNull($params['rules']);
        $this->assertSame('[]', $params['members']);
        $this->assertFalse($params['public']);
        $this->assertSame('member', $params['defaultRole']);
        $this->assertTrue($params['joinModeration']);
    }

    public function testAddMembers(): void
    {
        $members = [['sn' => 'user1']];

        $this->api->addMembers('chat1', $members);

        [$method, $path, $params] = $this->httpClientSpy->calls[0];
        $this->assertSame('get', $method);
        $this->assertSame('/v1/chats/members/add', $path);
        $this->assertSame('chat1', $params['chatId']);
        $this->assertSame(json_encode($members, JSON_THROW_ON_ERROR), $params['members']);
    }

    public function testRemoveMembers(): void
    {
        $this->api->removeMembers('chat1', [['sn' => 'user1']]);

        $this->assertSame('/v1/chats/members/delete', $this->httpClientSpy->calls[0][1]);
    }

    public function testSendAction(): void
    {
        $this->api->sendAction('chat1', 'typing');

        [$method, $path, $params] = $this->httpClientSpy->calls[0];
        $this->assertSame('/v1/chats/sendActions', $path);
        $this->assertSame('chat1', $params['chatId']);
        $this->assertSame('typing', $params['actions']);
    }

    public function testGetInfo(): void
    {
        $this->api->getInfo('chat1');

        $this->assertSame('/v1/chats/getInfo', $this->httpClientSpy->calls[0][1]);
        $this->assertSame('chat1', $this->httpClientSpy->calls[0][2]['chatId']);
    }

    public function testGetAdmins(): void
    {
        $this->api->getAdmins('chat1');

        $this->assertSame('/v1/chats/getAdmins', $this->httpClientSpy->calls[0][1]);
    }

    public function testGetMembersWithCursor(): void
    {
        $this->api->getMembers('chat1', cursor: 'abc');

        $params = $this->httpClientSpy->calls[0][2];
        $this->assertSame('chat1', $params['chatId']);
        $this->assertSame('abc', $params['cursor']);
    }

    public function testGetMembersWithoutCursorDoesNotIncludeCursorParam(): void
    {
        $this->api->getMembers('chat1');

        $params = $this->httpClientSpy->calls[0][2];
        $this->assertSame('chat1', $params['chatId']);
        $this->assertArrayNotHasKey('cursor', $params);
    }

    public function testGetBlockedUsers(): void
    {
        $this->api->getBlockedUsers('chat1');

        $this->assertSame('/v1/chats/getBlockedUsers', $this->httpClientSpy->calls[0][1]);
    }

    public function testGetPendingUsers(): void
    {
        $this->api->getPendingUsers('chat1');

        $this->assertSame('/v1/chats/getPendingUsers', $this->httpClientSpy->calls[0][1]);
    }

    public function testBlockUser(): void
    {
        $this->api->blockUser('chat1', 'user1', delLastMessages: true);

        $params = $this->httpClientSpy->calls[0][2];
        $this->assertSame('/v1/chats/blockUser', $this->httpClientSpy->calls[0][1]);
        $this->assertSame('user1', $params['userId']);
        $this->assertTrue($params['delLastMessages']);
    }

    public function testUnblockUser(): void
    {
        $this->api->unblockUser('chat1', 'user1');

        $this->assertSame('/v1/chats/unblockUser', $this->httpClientSpy->calls[0][1]);
        $this->assertSame('user1', $this->httpClientSpy->calls[0][2]['userId']);
    }

    public function testResolvePendingWithUserId(): void
    {
        $this->api->resolvePending('chat1', approve: true, userId: 'user1');

        $params = $this->httpClientSpy->calls[0][2];
        $this->assertSame('/v1/chats/resolvePending', $this->httpClientSpy->calls[0][1]);
        $this->assertTrue($params['approve']);
        $this->assertSame('user1', $params['userId']);
        $this->assertArrayNotHasKey('everyone', $params);
    }

    public function testResolvePendingWithEveryone(): void
    {
        $this->api->resolvePending('chat1', approve: false, everyone: true);

        $params = $this->httpClientSpy->calls[0][2];
        $this->assertFalse($params['approve']);
        $this->assertTrue($params['everyone']);
        $this->assertArrayNotHasKey('userId', $params);
    }

    /**
     * @return iterable<string, array{?string, ?bool}>
     */
    public static function invalidResolvePendingParamsProvider(): iterable
    {
        yield 'both null' => [null, null];
        yield 'both provided' => ['user1', true];
    }

    #[DataProvider('invalidResolvePendingParamsProvider')]
    public function testResolvePendingThrowsOnInvalidParams(?string $userId, ?bool $everyone): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Exactly one of userId or everyone must be provided');

        $this->api->resolvePending('chat1', approve: true, userId: $userId, everyone: $everyone);
    }

    public function testSetTitle(): void
    {
        $this->api->setTitle('chat1', 'New Title');

        $this->assertSame('/v1/chats/setTitle', $this->httpClientSpy->calls[0][1]);
        $this->assertSame('New Title', $this->httpClientSpy->calls[0][2]['title']);
    }

    public function testSetAvatar(): void
    {
        $this->api->setAvatar('chat1', '/tmp/avatar.png');

        [$method, $path, $params, $filePath] = $this->httpClientSpy->calls[0];
        $this->assertSame('postMultipart', $method);
        $this->assertSame('/v1/chats/avatar/set', $path);
        $this->assertSame('chat1', $params['chatId']);
        $this->assertSame('/tmp/avatar.png', $filePath);
    }

    public function testSetAbout(): void
    {
        $this->api->setAbout('chat1', 'About text');

        $this->assertSame('/v1/chats/setAbout', $this->httpClientSpy->calls[0][1]);
        $this->assertSame('About text', $this->httpClientSpy->calls[0][2]['about']);
    }

    public function testSetRules(): void
    {
        $this->api->setRules('chat1', 'Be nice');

        $this->assertSame('/v1/chats/setRules', $this->httpClientSpy->calls[0][1]);
        $this->assertSame('Be nice', $this->httpClientSpy->calls[0][2]['rules']);
    }
}
