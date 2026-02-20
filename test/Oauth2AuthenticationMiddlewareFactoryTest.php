<?php

declare(strict_types=1);

namespace LmcTest\Api\Auth\Oauth2;

use League\OAuth2\Server\ResourceServer;
use Lmc\Api\Auth\Oauth2\Exception\InvalidConfigException;
use Lmc\Api\Auth\Oauth2\Oauth2AuthenticationMiddleware;
use Lmc\Api\Auth\Oauth2\Oauth2AuthenticationMiddlewareFactory;
use Lmc\Api\Auth\Repository\UserRepositoryInterface;
use Override;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use stdClass;

#[CoversClass(Oauth2AuthenticationMiddlewareFactory::class)]
final class Oauth2AuthenticationMiddlewareFactoryTest extends TestCase
{
    private ContainerInterface&MockObject $container;
    private ResourceServer&MockObject $resourceServer;
    private UserRepositoryInterface&MockObject $userRepository;

    #[Override]
    public function setUp(): void
    {
        $this->container      = $this->createMock(ContainerInterface::class);
        $this->resourceServer = $this->createMock(ResourceServer::class);
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        parent::setUp();
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[AllowMockObjectsWithoutExpectations]
    public function testInvokeWithExpectedConfig(): void
    {
        $this->container->expects($this->exactly(2))->method('has')
            ->willReturnMap([
                [ResourceServer::class, true],
                ['lmc_api_oauth2_user_repository', true],
            ]);
        $this->container->expects($this->exactly(2))->method('get')
            ->willReturnMap([
                [ResourceServer::class, $this->resourceServer],
                ['lmc_api_oauth2_user_repository', $this->userRepository],
            ]);
        $factory = new Oauth2AuthenticationMiddlewareFactory();
        $this->assertInstanceOf(Oauth2AuthenticationMiddleware::class, $factory($this->container));
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[AllowMockObjectsWithoutExpectations]
    public function testInvokeMissingResourceServerConfig(): void
    {
        $this->container->expects($this->exactly(1))->method('has')
            ->willReturnMap([
                [ResourceServer::class, false],
            ]);
        $this->expectException(InvalidConfigException::class);
        $factory = new Oauth2AuthenticationMiddlewareFactory();
        $this->assertInstanceOf(Oauth2AuthenticationMiddleware::class, $factory($this->container));
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[AllowMockObjectsWithoutExpectations]
    public function testInvokeMissingUserRepositoryConfig(): void
    {
        $this->container->expects($this->exactly(2))->method('has')
            ->willReturnMap([
                [ResourceServer::class, true],
                ['lmc_api_oauth2_user_repository', false],
            ]);
        $this->container->expects($this->exactly(1))->method('get')
            ->willReturnMap([
                [ResourceServer::class, $this->resourceServer],
            ]);
        $this->expectException(InvalidConfigException::class);
        $factory = new Oauth2AuthenticationMiddlewareFactory();
        $this->assertInstanceOf(Oauth2AuthenticationMiddleware::class, $factory($this->container));
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[AllowMockObjectsWithoutExpectations]
    public function testInvokeInvalidUserRepositoryConfig(): void
    {
        $this->container->expects($this->exactly(2))->method('has')
            ->willReturnMap([
                [ResourceServer::class, true],
                ['lmc_api_oauth2_user_repository', true],
            ]);
        $this->container->expects($this->exactly(2))->method('get')
            ->willReturnMap([
                [ResourceServer::class, $this->resourceServer],
                ['lmc_api_oauth2_user_repository', new stdClass()],
            ]);
        $this->expectException(InvalidConfigException::class);
        $factory = new Oauth2AuthenticationMiddlewareFactory();
        $this->assertInstanceOf(Oauth2AuthenticationMiddleware::class, $factory($this->container));
    }
}
