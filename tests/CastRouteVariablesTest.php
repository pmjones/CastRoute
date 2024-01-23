<?php
declare(strict_types=1);

namespace CastRoute;

use FastRoute\RouteParser;
use FastRoute\DataGenerator;

class CastRouteVariablesTest extends CastRouteTestCase
{
    public function testRouteVariables() : void
    {
        $this->assertInstanceOf(CastRouteVariables::class, new CastRouteVariables());
    }

    public function testFixRouteVariables() : void
    {
        $castRoute = $this->newCastRoute();
        $routeVariables = $castRoute->getRouteVariables();

        // array
        $route = '/foo/{foo:array}';
        $expect = ['foo' => ['bar', 'baz', 'dib']];
        $actual = $routeVariables->fixVariables($route, ['foo' => 'bar,baz,dib']);
        $this->assertSame($expect, $actual);

        // bool
        $route = '/foo/{foo:bool}[/{bar:bool}[/{baz:bool}]]';
        $expect = ['foo' => true, 'bar' => false, 'baz' => false];
        $actual = $routeVariables->fixVariables(
            $route,
            ['foo' => 'y', 'bar' => 'n', 'baz' => ''],
        );
        $this->assertSame($expect, $actual);

        // float
        $route = '/foo/{foo:float}';
        $expect = ['foo' => 1.1];
        $actual = $routeVariables->fixVariables($route, ['foo' => '1.1']);
        $this->assertSame($expect, $actual);

        // int
        $route = '/foo/{foo:int}';
        $expect = ['foo' => 1];
        $actual = $routeVariables->fixVariables($route, ['foo' => '1']);
        $this->assertSame($expect, $actual);
    }
}
