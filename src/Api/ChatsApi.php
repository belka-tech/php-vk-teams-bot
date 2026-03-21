<?php

declare(strict_types=1);

namespace BelkaTech\VkTeamsBot\Api;

use BelkaTech\VkTeamsBot\Http\HttpClient;
use Exception;
use Psr\Http\Client\ClientExceptionInterface;

final readonly class ChatsApi
{
    public function __construct(
        private HttpClient $httpClient,
    ) {}

    /**
     * @param list<array{sn: string}> $members
     * @return array{
     *     ok: bool,
     *     sn: string,
     * }
     *
     * @throws ClientExceptionInterface
     */
    public function create(
        string $name,
        ?string $about = null,
        ?string $rules = null,
        array $members = [],
        bool $public = false,
        string $defaultRole = 'member',
        bool $joinModeration = true,
    ): array {
        /** @phpstan-ignore return.type */
        return $this->httpClient->get('/v1/chats/createChat', [
            'name' => $name,
            'about' => $about,
            'rules' => $rules,
            'members' => json_encode($members, flags: JSON_THROW_ON_ERROR),
            'public' => $public,
            'defaultRole' => $defaultRole,
            'joinModeration' => $joinModeration,
        ]);
    }

    /**
     * @param list<array{sn: string}> $members
     * @return array{ok: bool}
     *
     * @throws ClientExceptionInterface
     */
    public function addMembers(
        string $chatId,
        array $members,
    ): array {
        /** @phpstan-ignore return.type */
        return $this->httpClient->get('/v1/chats/members/add', [
            'chatId' => $chatId,
            'members' => json_encode($members, flags: JSON_THROW_ON_ERROR),
        ]);
    }

    /**
     * @param list<array{sn: string}> $members
     * @return array{ok: bool}
     *
     * @throws ClientExceptionInterface
     */
    public function removeMembers(
        string $chatId,
        array $members,
    ): array {
        /** @phpstan-ignore return.type */
        return $this->httpClient->get('/v1/chats/members/delete', [
            'chatId' => $chatId,
            'members' => json_encode($members, flags: JSON_THROW_ON_ERROR),
        ]);
    }

    /**
     * @return array{ok: bool}
     *
     * @throws ClientExceptionInterface
     */
    public function sendAction(
        string $chatId,
        string $action,
    ): array {
        /** @phpstan-ignore return.type */
        return $this->httpClient->get('/v1/chats/sendActions', [
            'chatId' => $chatId,
            'actions' => $action,
        ]);
    }

    /**
     * @return array{
     *     ok: bool,
     *     type: string,
     *     title?: string,
     *     about?: string,
     *     rules?: string,
     *     firstName?: string,
     *     lastName?: string,
     *     nick?: string,
     *     isBot?: bool,
     *     public?: bool,
     *     inviteLink?: string,
     *     joinModeration?: bool,
     *     photo?: list<array{url: string}>,
     * }
     *
     * @throws ClientExceptionInterface
     */
    public function getInfo(
        string $chatId,
    ): array {
        /** @phpstan-ignore return.type */
        return $this->httpClient->get('/v1/chats/getInfo', [
            'chatId' => $chatId,
        ]);
    }

    /**
     * @return array{
     *     ok: bool,
     *     admins: list<array{userId: string, creator: bool}>,
     * }
     *
     * @throws ClientExceptionInterface
     */
    public function getAdmins(
        string $chatId,
    ): array {
        /** @phpstan-ignore return.type */
        return $this->httpClient->get('/v1/chats/getAdmins', [
            'chatId' => $chatId,
        ]);
    }

    /**
     * @return array{
     *     ok: bool,
     *     members: list<array{sn: string, role: string}>,
     * }
     *
     * @throws ClientExceptionInterface
     */
    public function getMembers(
        string $chatId,
        ?string $cursor = null,
    ): array {
        $params = ['chatId' => $chatId];

        if ($cursor !== null) {
            $params['cursor'] = $cursor;
        }

        /** @phpstan-ignore return.type */
        return $this->httpClient->get('/v1/chats/getMembers', $params);
    }

    /**
     * @return array{
     *     ok: bool,
     *     users: list<array{userId: string}>,
     * }
     *
     * @throws ClientExceptionInterface
     */
    public function getBlockedUsers(
        string $chatId,
    ): array {
        /** @phpstan-ignore return.type */
        return $this->httpClient->get('/v1/chats/getBlockedUsers', [
            'chatId' => $chatId,
        ]);
    }

    /**
     * @return array{
     *     ok: bool,
     *     users: list<array{userId: string}>,
     * }
     *
     * @throws ClientExceptionInterface
     */
    public function getPendingUsers(
        string $chatId,
    ): array {
        /** @phpstan-ignore return.type */
        return $this->httpClient->get('/v1/chats/getPendingUsers', [
            'chatId' => $chatId,
        ]);
    }

    /**
     * @return array{ok: bool}
     *
     * @throws ClientExceptionInterface
     */
    public function blockUser(
        string $chatId,
        string $userId,
        bool $delLastMessages,
    ): array {
        /** @phpstan-ignore return.type */
        return $this->httpClient->get('/v1/chats/blockUser', [
            'chatId' => $chatId,
            'userId' => $userId,
            'delLastMessages' => $delLastMessages,
        ]);
    }

    /**
     * @return array{ok: bool}
     *
     * @throws ClientExceptionInterface
     */
    public function unblockUser(
        string $chatId,
        string $userId,
    ): array {
        /** @phpstan-ignore return.type */
        return $this->httpClient->get('/v1/chats/unblockUser', [
            'chatId' => $chatId,
            'userId' => $userId,
        ]);
    }

    /**
     * @return array{ok: bool}
     *
     * @throws ClientExceptionInterface
     * @throws Exception
     */
    public function resolvePending(
        string $chatId,
        bool $approve,
        ?string $userId = null,
        ?bool $everyone = null,
    ): array {
        if ($userId !== null) {
            /** @phpstan-ignore return.type */
            return $this->httpClient->get('/v1/chats/resolvePending', [
                'chatId' => $chatId,
                'approve' => $approve,
                'userId' => $userId,
            ]);
        }

        if ($everyone !== null) {
            /** @phpstan-ignore return.type */
            return $this->httpClient->get('/v1/chats/resolvePending', [
                'chatId' => $chatId,
                'approve' => $approve,
                'everyone' => $everyone,
            ]);
        }

        throw new Exception('userId or everyone must be provided');
    }

    /**
     * @return array{ok: bool}
     *
     * @throws ClientExceptionInterface
     */
    public function setTitle(
        string $chatId,
        string $title,
    ): array {
        /** @phpstan-ignore return.type */
        return $this->httpClient->get('/v1/chats/setTitle', [
            'chatId' => $chatId,
            'title' => $title,
        ]);
    }

    /**
     * @return array{ok: bool}
     *
     * @throws ClientExceptionInterface
     */
    public function setAvatar(
        string $chatId,
        string $imagePath,
    ): array {
        /** @phpstan-ignore return.type */
        return $this->httpClient->postMultipart(
            '/v1/chats/avatar/set',
            ['chatId' => $chatId],
            $imagePath,
        );
    }

    /**
     * @return array{ok: bool}
     *
     * @throws ClientExceptionInterface
     */
    public function setAbout(
        string $chatId,
        string $about,
    ): array {
        /** @phpstan-ignore return.type */
        return $this->httpClient->get('/v1/chats/setAbout', [
            'chatId' => $chatId,
            'about' => $about,
        ]);
    }

    /**
     * @return array{ok: bool}
     *
     * @throws ClientExceptionInterface
     */
    public function setRules(
        string $chatId,
        string $rules,
    ): array {
        /** @phpstan-ignore return.type */
        return $this->httpClient->get('/v1/chats/setRules', [
            'chatId' => $chatId,
            'rules' => $rules,
        ]);
    }
}
