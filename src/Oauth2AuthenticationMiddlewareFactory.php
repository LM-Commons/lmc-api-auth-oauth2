<?php

declare(strict_types=1);

namespace Lmc\Api\Auth\Oauth2;

use League\OAuth2\Server\ResourceServer;
use Lmc\Api\Auth\Repository\UserRepositoryInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

final class Oauth2AuthenticationMiddlewareFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): Oauth2AuthenticationMiddleware
    {
        /** @var ResourceServer|null $resourceServer */
        $resourceServer = $container->has(ResourceServer::class)
            ? $container->get(ResourceServer::class)
            : null;

        if (null === $resourceServer) {
            throw new Exception\InvalidConfigException(
                'OAuth2 resource server is missing from configuration.',
            );
        }

        /** @var UserRepositoryInterface $userRepository */
        $userRepository = $container->has('lmc_api_oauth2_user_repository')
            ? $container->get('lmc_api_oauth2_user_repository')
            : null;

        if (! $userRepository instanceof UserRepositoryInterface) {
            throw new Exception\InvalidConfigException(
                'Oauth2 user repository is missing from configuration or is invalid.',
            );
        }

        return new Oauth2AuthenticationMiddleware(
            $resourceServer,
            $userRepository,
        );
    }
}
