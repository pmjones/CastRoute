<?php
declare(strict_types=1);

namespace CastRoute;

class CastRouteDispatcherTest extends CastRouteTestCase
{
    public function testDispatch() : void
    {
        $castRoute = $this->newCastRoute();
        $dispatcher = $castRoute->getDispatcher();

        // no such path
        $expect = [CastRouteDispatcher::NOT_FOUND];
        $actual = $dispatcher->dispatch('GET', '/no/such/path');
        $this->assertSame($expect, $actual);

        // from addRoute()
        $expect = [CastRouteDispatcher::FOUND, 'GetUserAction', ['id' => 88]];
        $actual = $dispatcher->dispatch('GET', '/user/88');
        $this->assertSame($expect, $actual);

        // from http method as method name
        $expect = [CastRouteDispatcher::FOUND, 'EditUserAction', ['id' => 88]];
        $actual = $dispatcher->dispatch('PATCH', '/user/88');
        $this->assertSame($expect, $actual);

        // from map(), no optionals
        $expect = [
            CastRouteDispatcher::FOUND,
            'GetArchiveAction',
            ['username' => 'bolivar'],
        ];
        $actual = $dispatcher->dispatch('GET', '/archive/bolivar');
        $this->assertSame($expect, $actual);

        // optional year
        $expect = [
            CastRouteDispatcher::FOUND,
            'GetArchiveAction',
            ['username' => 'bolivar', 'year' => '1979'],
        ];
        $actual = $dispatcher->dispatch('GET', '/archive/bolivar/1979');
        $this->assertSame($expect, $actual);

        // optional year and month
        $expect = [
            CastRouteDispatcher::FOUND,
            'GetArchiveAction',
            ['username' => 'bolivar', 'year' => '1979', 'month' => '11'],
        ];
        $actual = $dispatcher->dispatch('GET', '/archive/bolivar/1979/11');
        $this->assertSame($expect, $actual);

        // optional year, month, and day
        $expect = [
            CastRouteDispatcher::FOUND,
            'GetArchiveAction',
            ['username' => 'bolivar', 'year' => '1979', 'month' => '11', 'day' => '07'],
        ];
        $actual = $dispatcher->dispatch('GET', '/archive/bolivar/1979/11/07');
        $this->assertSame($expect, $actual);
    }
}
