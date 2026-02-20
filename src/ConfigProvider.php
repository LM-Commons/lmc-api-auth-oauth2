<?php

declare(strict_types=1);

namespace Lmc\Api\Auth\Oauth2;

final class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
        ];
    }

    private function getDependencies(): array
    {
        return [
            'factories' => [
                Oauth2AuthenticationMiddleware::class => Oauth2AuthenticationMiddlewareFactory::class,
            ],
        ];
    }
}
