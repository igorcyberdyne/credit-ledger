<?php

declare(strict_types=1);

namespace App\Tests\Functional\Security;

use App\Tests\Tools\ApiRouteProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;

final class ApiSecurityTest extends WebTestCase
{
    protected ApiRouteProvider $routes;
    protected KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = ApiSecurityTest::createClient();

        $this->routes = new ApiRouteProvider(
            ApiSecurityTest::getContainer()->get(RouterInterface::class),
        );
    }

    public function testProtectedRoutesRequireAuthentication(): void
    {
        foreach ($this->routes->getProtectedRoutes() as $route) {
            $this->client->request($route->method, $route->path);

            self::assertTrue(
                401 === $this->client->getResponse()->getStatusCode(),
                sprintf(
                    '%s %s should require authentication.',
                    $route->method,
                    $route->path,
                ),
            );
        }
    }
}
