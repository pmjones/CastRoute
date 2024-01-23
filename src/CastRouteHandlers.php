<?php
declare(strict_types=1);

namespace CastRoute;

use FastRoute\Dispatcher;

/**
 * @phpstan-import-type routeinfo from CastRouteDispatcher
 */
class CastRouteHandlers
{
    public const HTTP_METHOD = 0;

    public const ROUTE = 1;

    public const ROUTE_DATAS = 2;

    /**
     * @param array<string, array{string, string, mixed[]}> $data
     */
    public function __construct(
        protected CastRouteVariables $routeVariables,
        protected array $data = [],
    ) {
    }

    /**
     * @return array<string, mixed[]> $data
     */
    public function getData() : array
    {
        return $this->data;
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function getUrls() : array
    {
        $urls = [];

        foreach ($this->data as $handler => $info) {
            [$method, $route, $routeDatas] = $info;
            $urls[$route][$method] = $handler;
        }

        ksort($urls);

        foreach ($urls as $route => &$methodHandler) {
            if (isset($methodHandler['GET']) && ! isset($methodHandler['HEAD'])) {
                $methodHandler['HEAD'] = $methodHandler['GET'];
            }

            ksort($methodHandler);
        }

        return $urls;
    }

    /**
     * @param mixed[] $routeDatas
     */
    public function setHandler(
        string $handler,
        string $httpMethod,
        string $route,
        array $routeDatas,
    ) : void
    {
        $this->data[$handler] = [$httpMethod, $route, $routeDatas];
    }

    /**
     * @return ?mixed[]
     */
    public function getRouteDatas(string $handler) : ?array
    {
        /** @var ?mixed[] */
        return array_key_exists($handler, $this->data)
            ? $this->data[$handler][static::ROUTE_DATAS]
            : null;
    }

    /**
     * @param routeinfo $routeInfo
     * @return routeinfo
     */
    public function fixRouteInfo(array $routeInfo) : array
    {
        if ($routeInfo[0] !== Dispatcher::FOUND) {
            return $routeInfo;
        }

        $handler = $routeInfo[1] ?? null;

        if (! is_string($handler)) {
            return $routeInfo;
        }

        $route = array_key_exists($handler, $this->data)
            ? $this->data[$handler][static::ROUTE]
            : null;

        if (is_string($route)) {
            /** @var array<string, string> */
            $variables = $routeInfo[2] ?? [];
            $routeInfo[2] = $this->routeVariables->fixVariables($route, $variables);
        }

        /** @var routeinfo */
        return $routeInfo;
    }
}
