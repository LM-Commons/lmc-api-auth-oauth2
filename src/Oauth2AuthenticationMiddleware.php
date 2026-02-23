<?php

declare(strict_types=1);

namespace Lmc\Api\Auth\Oauth2;

use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\ResourceServer;
use Lmc\Api\Auth\Identity\AuthenticatedIdentity;
use Lmc\Api\Auth\Identity\GuestIdentity;
use Lmc\Api\Auth\Identity\IdentityInterface;
use Lmc\Api\Auth\Repository\UserRepositoryInterface;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class Oauth2AuthenticationMiddleware implements MiddlewareInterface
{
    public function __construct(
        private ResourceServer $resourceServer,
        private UserRepositoryInterface $userRepository,
    ) {
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // If there is already an identity, do nothing
        $identity = $request->getAttribute(IdentityInterface::class);
        if (null !== $identity && ! $identity instanceof GuestIdentity) {
            return $handler->handle($request);
        }

        try {
            $result = $this->resourceServer->validateAuthenticatedRequest($request);
            /** @var int|string|null $userId */
            $userId = $result->getAttribute('oauth_user_id');
            if (isset($userId)) {
                $identity = $this->userRepository->getByUserId($userId);
                if (null === $identity) {
                    $authIdentity = new GuestIdentity();
                } else {
                    $authIdentity = new AuthenticatedIdentity($identity);
                }
            } else {
                $authIdentity = new GuestIdentity();
            }
        } catch (OAuthServerException $e) {
            $authIdentity = new GuestIdentity();
        }
        return $handler->handle($request->withAttribute(IdentityInterface::class, $authIdentity));
    }
}
