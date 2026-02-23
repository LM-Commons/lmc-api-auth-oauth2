<?php

declare(strict_types=1);

namespace LmcTest\Api\Auth\Oauth2;

use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\ResourceServer;
use Lmc\Api\Auth\Identity\AuthenticatedIdentity;
use Lmc\Api\Auth\Identity\GuestIdentity;
use Lmc\Api\Auth\Identity\IdentityInterface;
use Lmc\Api\Auth\Oauth2\Oauth2AuthenticationMiddleware;
use Lmc\Api\Auth\Repository\UserRepositoryInterface;
use LmcTest\Api\Auth\Oauth2\Assets\Identity;
use LmcTest\Api\Auth\Oauth2\Assets\IdentityGetId;
use Override;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

#[CoversClass(Oauth2AuthenticationMiddleware::class)]
final class Oauth2AuthenticationMiddlewareTest extends TestCase
{
    private ResourceServer&MockObject $resourceServer;
    private ServerRequestInterface&MockObject $request;
    private RequestHandlerInterface&MockObject $handler;
    private UserRepositoryInterface&MockObject $userRepository;
    private ServerRequestInterface&MockObject $result;

    #[Override]
    public function setUp(): void
    {
        $this->resourceServer = $this->createMock(ResourceServer::class);
        $this->request        = $this->createMock(ServerRequestInterface::class);
        $this->result         = $this->createMock(ServerRequestInterface::class);
        $this->handler        = $this->createMock(RequestHandlerInterface::class);
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        parent::setUp();
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testWithExistingIdentity(): void
    {
        $identity = new AuthenticatedIdentity(new IdentityGetId());
        $this->request->expects($this->once())->method('getAttribute')
            ->with(IdentityInterface::class)
            ->willReturn($identity);
        $this->handler->expects($this->once())->method('handle')->with($this->request);
        $middleware = new Oauth2AuthenticationMiddleware($this->resourceServer, $this->userRepository);
        $middleware->process($this->request, $this->handler);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testNoUserId(): void
    {
        $this->resourceServer->expects($this->once())->method('validateAuthenticatedRequest')
            ->with($this->request)->willReturn($this->result);
        $this->request->expects($this->once())->method('getAttribute')
            ->with(IdentityInterface::class)
            ->willReturn(null);
        $this->result->expects($this->once())->method('getAttribute')
            ->with('oauth_user_id')->willReturn(null);
        $this->request->expects($this->once())->method('withAttribute')
            ->with(IdentityInterface::class, new GuestIdentity())
            ->willReturnSelf();
        $this->handler->expects($this->once())->method('handle')->with($this->request);
        $middleware = new Oauth2AuthenticationMiddleware($this->resourceServer, $this->userRepository);
        $middleware->process($this->request, $this->handler);
    }

    public function testWithUserId(): void
    {
        $this->resourceServer->expects($this->once())->method('validateAuthenticatedRequest')
            ->with($this->request)->willReturn($this->result);
        $this->request->expects($this->once())->method('getAttribute')
            ->with(IdentityInterface::class)
            ->willReturn(null);
        $this->result->expects($this->once())->method('getAttribute')
            ->with('oauth_user_id')->willReturn('foo');
        $this->userRepository->expects($this->once())->method('getByUserId')
            ->with('foo')->willReturn(new Identity());
        $this->request->expects($this->once())->method('withAttribute')
            ->with(IdentityInterface::class)
            ->willReturnSelf();
        $this->handler->expects($this->once())->method('handle')->with($this->request);
        $middleware = new Oauth2AuthenticationMiddleware($this->resourceServer, $this->userRepository);
        $middleware->process($this->request, $this->handler);
    }

    public function testWithNullUser(): void
    {
        $this->resourceServer->expects($this->once())->method('validateAuthenticatedRequest')
            ->with($this->request)->willReturn($this->result);
        $this->request->expects($this->once())->method('getAttribute')
            ->with(IdentityInterface::class)
            ->willReturn(null);
        $this->result->expects($this->once())->method('getAttribute')
            ->with('oauth_user_id')->willReturn('foo');
        $this->userRepository->expects($this->once())->method('getByUserId')
            ->with('foo')->willReturn(null);
        $this->request->expects($this->once())->method('withAttribute')
            ->with(IdentityInterface::class)
            ->willReturnSelf();
        $this->handler->expects($this->once())->method('handle')->with($this->request);
        $middleware = new Oauth2AuthenticationMiddleware($this->resourceServer, $this->userRepository);
        $middleware->process($this->request, $this->handler);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testWithException(): void
    {
        $this->resourceServer->expects($this->once())->method('validateAuthenticatedRequest')
            ->with($this->request)->willThrowException(new OAuthServerException(
                'foo',
                1,
                'bar',
            ));
        $this->request->expects($this->once())->method('getAttribute')
            ->with(IdentityInterface::class)
            ->willReturn(null);
        $this->request->expects($this->once())->method('withAttribute')
            ->with(IdentityInterface::class)
            ->willReturnSelf();
        $this->handler->expects($this->once())->method('handle')->with($this->request);
        $middleware = new Oauth2AuthenticationMiddleware($this->resourceServer, $this->userRepository);
        $middleware->process($this->request, $this->handler);
    }
}
