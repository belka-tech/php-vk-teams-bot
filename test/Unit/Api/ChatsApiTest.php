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
        // GIVEN: a list of members
        $members = [['sn' => 'user1'], ['sn' => 'user2']];

        // WHEN: create is called with all params
        $this->api->create('Test Chat', about: 'desc', members: $members, public: true);

        // THEN: correct request is sent
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
        // WHEN: create is called with only name
        $this->api->create('Chat');

        // THEN: default values are used
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
        // GIVEN: a list of members to add
        $members = [['sn' => 'user1']];

        // WHEN: addMembers is called
        $this->api->addMembers('chat1', $members);

        // THEN: correct request is sent
        [$method, $path, $params] = $this->httpClientSpy->calls[0];
        $this->assertSame('get', $method);
        $this->assertSame('/v1/chats/members/add', $path);
        $this->assertSame('chat1', $params['chatId']);
        $this->assertSame(json_encode($members, JSON_THROW_ON_ERROR), $params['members']);
    }

    public function testRemoveMembers(): void
    {
        // WHEN: removeMembers is called
        $this->api->removeMembers('chat1', [['sn' => 'user1']]);

        // THEN: correct path is used
        $this->assertSame('/v1/chats/members/delete', $this->httpClientSpy->calls[0][1]);
    }

    public function testSendAction(): void
    {
        // WHEN: sendAction is called
        $this->api->sendAction('chat1', 'typing');

        // THEN: correct path and params are sent
        [$method, $path, $params] = $this->httpClientSpy->calls[0];
        $this->assertSame('/v1/chats/sendActions', $path);
        $this->assertSame('chat1', $params['chatId']);
        $this->assertSame('typing', $params['actions']);
    }

    public function testGetInfo(): void
    {
        // WHEN: getInfo is called
        $this->api->getInfo('chat1');

        // THEN: correct path and chatId are sent
        $this->assertSame('/v1/chats/getInfo', $this->httpClientSpy->calls[0][1]);
        $this->assertSame('chat1', $this->httpClientSpy->calls[0][2]['chatId']);
    }

    public function testGetAdmins(): void
    {
        // WHEN: getAdmins is called
        $this->api->getAdmins('chat1');

        // THEN: correct path is used
        $this->assertSame('/v1/chats/getAdmins', $this->httpClientSpy->calls[0][1]);
    }

    public function testGetMembersWithCursor(): void
    {
        // WHEN: getMembers is called with cursor
        $this->api->getMembers('chat1', cursor: 'abc');

        // THEN: cursor param is included
        $params = $this->httpClientSpy->calls[0][2];
        $this->assertSame('chat1', $params['chatId']);
        $this->assertSame('abc', $params['cursor']);
    }

    public function testGetMembersWithoutCursorDoesNotIncludeCursorParam(): void
    {
        // WHEN: getMembers is called without cursor
        $this->api->getMembers('chat1');

        // THEN: cursor param is absent
        $params = $this->httpClientSpy->calls[0][2];
        $this->assertSame('chat1', $params['chatId']);
        $this->assertArrayNotHasKey('cursor', $params);
    }

    public function testGetBlockedUsers(): void
    {
        // WHEN: getBlockedUsers is called
        $this->api->getBlockedUsers('chat1');

        // THEN: correct path is used
        $this->assertSame('/v1/chats/getBlockedUsers', $this->httpClientSpy->calls[0][1]);
    }

    public function testGetPendingUsers(): void
    {
        // WHEN: getPendingUsers is called
        $this->api->getPendingUsers('chat1');

        // THEN: correct path is used
        $this->assertSame('/v1/chats/getPendingUsers', $this->httpClientSpy->calls[0][1]);
    }

    public function testBlockUser(): void
    {
        // WHEN: blockUser is called with delLastMessages=true
        $this->api->blockUser('chat1', 'user1', delLastMessages: true);

        // THEN: correct params are sent
        $params = $this->httpClientSpy->calls[0][2];
        $this->assertSame('/v1/chats/blockUser', $this->httpClientSpy->calls[0][1]);
        $this->assertSame('user1', $params['userId']);
        $this->assertTrue($params['delLastMessages']);
    }

    public function testUnblockUser(): void
    {
        // WHEN: unblockUser is called
        $this->api->unblockUser('chat1', 'user1');

        // THEN: correct path and userId are sent
        $this->assertSame('/v1/chats/unblockUser', $this->httpClientSpy->calls[0][1]);
        $this->assertSame('user1', $this->httpClientSpy->calls[0][2]['userId']);
    }

    public function testResolvePendingWithUserId(): void
    {
        // WHEN: resolvePending is called with userId
        $this->api->resolvePending('chat1', approve: true, userId: 'user1');

        // THEN: userId is sent, everyone is absent
        $params = $this->httpClientSpy->calls[0][2];
        $this->assertSame('/v1/chats/resolvePending', $this->httpClientSpy->calls[0][1]);
        $this->assertTrue($params['approve']);
        $this->assertSame('user1', $params['userId']);
        $this->assertArrayNotHasKey('everyone', $params);
    }

    public function testResolvePendingWithEveryone(): void
    {
        // WHEN: resolvePending is called with everyone=true
        $this->api->resolvePending('chat1', approve: false, everyone: true);

        // THEN: everyone is sent, userId is absent
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
        // THEN: exception is expected
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Exactly one of userId or everyone must be provided');

        // WHEN: resolvePending is called with invalid param combination
        $this->api->resolvePending('chat1', approve: true, userId: $userId, everyone: $everyone);
    }

    public function testSetTitle(): void
    {
        // WHEN: setTitle is called
        $this->api->setTitle('chat1', 'New Title');

        // THEN: correct path and title are sent
        $this->assertSame('/v1/chats/setTitle', $this->httpClientSpy->calls[0][1]);
        $this->assertSame('New Title', $this->httpClientSpy->calls[0][2]['title']);
    }

    public function testSetAvatar(): void
    {
        // WHEN: setAvatar is called
        $this->api->setAvatar('chat1', '/tmp/avatar.png');

        // THEN: multipart request is sent with file path
        [$method, $path, $params, $filePath] = $this->httpClientSpy->calls[0];
        $this->assertSame('postMultipart', $method);
        $this->assertSame('/v1/chats/avatar/set', $path);
        $this->assertSame('chat1', $params['chatId']);
        $this->assertSame('/tmp/avatar.png', $filePath);
    }

    public function testSetAbout(): void
    {
        // WHEN: setAbout is called
        $this->api->setAbout('chat1', 'About text');

        // THEN: correct path and about are sent
        $this->assertSame('/v1/chats/setAbout', $this->httpClientSpy->calls[0][1]);
        $this->assertSame('About text', $this->httpClientSpy->calls[0][2]['about']);
    }

    public function testSetRules(): void
    {
        // WHEN: setRules is called
        $this->api->setRules('chat1', 'Be nice');

        // THEN: correct path and rules are sent
        $this->assertSame('/v1/chats/setRules', $this->httpClientSpy->calls[0][1]);
        $this->assertSame('Be nice', $this->httpClientSpy->calls[0][2]['rules']);
    }

    public function testPinMessage(): void
    {
        // WHEN: pinMessage is called
        $this->api->pinMessage('group1', 42);

        // THEN: correct path and params are sent
        [$method, $path, $params] = $this->httpClientSpy->calls[0];
        $this->assertSame('get', $method);
        $this->assertSame('/v1/chats/pinMessage', $path);
        $this->assertSame('group1', $params['groupOrChannelId']);
        $this->assertSame(42, $params['msgId']);
    }

    public function testUnpinMessage(): void
    {
        // WHEN: unpinMessage is called
        $this->api->unpinMessage('group1', 42);

        // THEN: correct path is used
        $this->assertSame('/v1/chats/unpinMessage', $this->httpClientSpy->calls[0][1]);
    }
}
