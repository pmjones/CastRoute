<?php
declare(strict_types=1);

namespace CastRoute;

use FastRoute\Dispatcher;

/**
 * @phpstan-type routeinfo array{0: int, 1?: mixed, 2?:array<string, mixed>}
 */
class CastRouteDispatcher implements Dispatcher
{
    public function __construct(
        protected Dispatcher $fastRouteDispatcher,
        protected CastRouteHandlers $routeHandlers,
    ) {
    }

    /**
     * @inheritdoc
     * @return routeinfo
     */
    public function dispatch($method, $url)
    {
        /** @var routeinfo */
        $routeInfo = $this->fastRouteDispatcher->dispatch($method, $url);
        return $this->routeHandlers->fixRouteInfo($routeInfo);
    }
}
