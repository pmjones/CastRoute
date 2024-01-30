<?php
declare(strict_types=1);

namespace CastRoute;

use FastRoute\RouteParser;
use FastRoute\DataGenerator;

class CastRouteHandlersTest extends CastRouteTestCase
{
    public function testFixRouteInfo_noNeedToFix() : void
    {
        $castRoute = $this->newCastRoute();
        $routeHandlers = $castRoute->getRouteHandlers();

        // fake $routeInfo for NOT_FOUND
        $expect = [CastRouteDispatcher::NOT_FOUND];
        $actual = $routeHandlers->fixRouteInfo($expect);
        $this->assertSame($expect, $actual);

        // fake $routeInfo for non-string handler
        $expect = [CastRouteDispatcher::FOUND, ['foo', 'bar'], []];
        $actual = $routeHandlers->fixRouteInfo($expect);
        $this->assertSame($expect, $actual);

        // fake $routeInfo for missing handler
        $expect = [CastRouteDispatcher::FOUND, 'NoSuchHandler', []];
        $actual = $routeHandlers->fixRouteInfo($expect);
        $this->assertSame($expect, $actual);
    }

    public function testGetUrls() : void
    {
        $castRoute = $this->newCastRoute();
        $routeHandlers = $castRoute->getRouteHandlers();

        /** @php-styler-expansive */
        $expect = [
            '/archive/{username}[/{year:year}[/{month:month}[/{day:day}]]]' => [
                'GET' => 'GetArchiveAction',
                'HEAD' => 'GetArchiveAction',
            ],
            '/article' => [
                'POST' => 'PostArticleAction',
            ],
            '/article/{id:int}' => [
                'GET' => 'GetArticleAction',
                'HEAD' => 'GetArticleAction',
                'PATCH' => 'PatchArticleAction',
            ],
            '/user' => [
                'POST' => 'PostUserAction',
            ],
            '/user/{id:int}' => [
                'GET' => 'GetUserAction',
                'HEAD' => 'GetUserAction',
                'PATCH' => 'EditUserAction',
            ],
        ];
        $actual = $routeHandlers->getUrls();
        $this->assertSame($expect, $actual);
    }
}
