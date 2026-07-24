<?php

declare(strict_types=1);

namespace App\Tests\Tools;

use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouterInterface;

final readonly class ApiRouteProvider
{
    public function __construct(
        private RouterInterface $router,
    ) {
    }

    /**
     * Retourne toutes les routes API protégées.
     *
     * @return iterable<ApiRoute>
     */
    public function getProtectedRoutes(): iterable
    {
        foreach ($this->router->getRouteCollection() as $name => $route) {
            if (!$this->isProtectedRoute($name, $route)) {
                continue;
            }

            $methods = $route->getMethods();

            if ([] === $methods) {
                $methods = ['GET'];
            }

            foreach ($methods as $method) {
                if (empty($method)) {
                    continue;
                }

                yield new ApiRoute(
                    name: $name,
                    method: $method,
                    path: $this->normalizePath($route->getPath()),
                );
            }
        }
    }

    /**
     * Retourne toutes les routes API.
     *
     * @return iterable<array{
     *     name:string,
     *     methods:list<string>,
     *     path:string
     * }>
     */
    public function getAllRoutes(): iterable
    {
        foreach ($this->router->getRouteCollection() as $name => $route) {
            $methods = $route->getMethods() ?: ['GET'];
            foreach ($methods as $method) {
                if (empty($method)) {
                    continue;
                }

                yield new ApiRoute(
                    name: $name,
                    method: $method,
                    path: $this->normalizePath($route->getPath()),
                );
            }
        }
    }

    /**
     * Vérifie si la route fait partie de l'API.
     */
    private function isProtectedRoute(
        string $name,
        Route $route,
    ): bool {
        $path = $route->getPath();

        if (!str_starts_with($path, '/api')) {
            return false;
        }

        /*
         * Documentation
         */

        if (str_contains($path, '/doc')) {
            return false;
        }

        /*
         * Routes publiques
         */

        if (\in_array($name, [
            'api_login_check',
            'gesdinet_jwt_refresh_token',
            'lexik_jwt_authentication_token',
            'lexik_jwt_authentication_refresh_token',
            'nelmio_api_doc',
            'nelmio_api_doc_index',
        ], true)) {
            return false;
        }

        return true;
    }

    /**
     * Remplace les paramètres par des valeurs valides.
     */
    private function normalizePath(
        string $path,
    ): string {
        return preg_replace_callback(
            '/\{([^}]+)\}/',
            static function (array $matches): string {
                $parameter = strtolower($matches[1]);

                return match (true) {
                    str_contains($parameter, 'uuid') => '01999999-9999-7999-9999-999999999999',

                    str_contains($parameter, 'id') => '1',

                    str_contains($parameter, 'customer') => '01999999-9999-7999-9999-999999999999',

                    str_contains($parameter, 'ledger') => '01999999-9999-7999-9999-999999999999',

                    default => 'test',
                };
            },
            $path,
        );
    }
}
