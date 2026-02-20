<?php

declare(strict_types=1);

namespace LmcTest\Api\Auth\Oauth2\Assets;

use Lmc\Api\Auth\Identity\IdentityInterface;
use Override;

final class Identity implements IdentityInterface
{
    #[Override]
    public function getAuthenticationIdentity(): mixed
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function getRoleId(): string
    {
        return 'foo';
    }
}
