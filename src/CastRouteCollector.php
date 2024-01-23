<?php
declare(strict_types=1);

namespace CastRoute;

use FastRoute\DataGenerator;
use FastRoute\RouteCollector;
use FastRoute\RouteParser;

class CastRouteCollector extends RouteCollector
{
    protected CastRouteHandlers $routeHandlers;

    protected CastRouteVariables $routeVariables;

    public function __construct(
        RouteParser $routeParser,
        DataGenerator $dataGenerator,
        CastRouteHandlers $routeHandlers,
        CastRouteVariables $routeVariables,
    ) {
        parent::__construct($routeParser, $dataGenerator);
        $this->routeHandlers = $routeHandlers;
        $this->routeVariables = $routeVariables;
    }

    /**
     * @inheritdoc
     */
    public function addRoute($httpMethod, $route, $handler) : void
    {
        $route = $this->currentGroupPrefix . $route;
        $fixed = $this->routeVariables->fixRoute($route);
        $routeDatas = $this->routeParser->parse($fixed);

        foreach ((array) $httpMethod as $method) {
            foreach ($routeDatas as $routeData) {
                $this->dataGenerator->addRoute($method, $routeData, $handler);
            }

            if (is_string($handler)) {
                $this->routeHandlers
                    ->setHandler($handler, $method, $route, $routeDatas);
            }
        }
    }
}
