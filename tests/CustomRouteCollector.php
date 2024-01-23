<?php
declare(strict_types=1);

namespace CastRoute;

class CustomRouteCollector extends CastRouteCollector
{
    public function map(string $route, string $handlerClass) : void
    {
        $parts = explode('\\', $handlerClass);
        preg_match('/^([A-Z][a-z]+).*/', end($parts), $matches);
        $this->addRoute(strtoupper($matches[1]), $route, $handlerClass);
    }
}
