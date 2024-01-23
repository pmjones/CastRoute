<?php
declare(strict_types=1);

namespace CastRoute;

use FastRoute\RouteParser;
use FastRoute\DataGenerator;

class CastRouteGeneratorTest extends CastRouteTestCase
{
    public function testGenerate() : void
    {
        $castRoute = $this->newCastRoute();
        $generator = $castRoute->getGenerator();

        // has only required values
        $expect = '/archive/bolivar';
        $actual = $generator->generate('GetArchiveAction', ['username' => 'bolivar']);
        $this->assertSame($expect, $actual);

        // has optional value for year
        $expect = '/archive/bolivar/1979';
        $actual = $generator->generate(
            'GetArchiveAction',
            ['username' => 'bolivar', 'year' => 1979],
        );
        $this->assertSame($expect, $actual);

        // has optional values for year and month
        $expect = '/archive/bolivar/1979/11';
        $actual = $generator->generate(
            'GetArchiveAction',
            ['username' => 'bolivar', 'year' => 1979, 'month' => 11],
        );
        $this->assertSame($expect, $actual);

        // has optional values for year, month, and day
        $expect = '/archive/bolivar/1979/11/07';
        $actual = $generator->generate(
            'GetArchiveAction',
            ['username' => 'bolivar', 'year' => 1979, 'month' => 11, 'day' => '07'],
        );
        $this->assertSame($expect, $actual);

        // has optional values for year and day, but not month
        $expect = '/archive/bolivar/1979';
        $actual = $generator->generate(
            'GetArchiveAction',
            ['username' => 'bolivar', 'year' => 1979, 'day' => '07'],
        );
        $this->assertSame($expect, $actual);
    }

    public function testGenerate_missingHandler() : void
    {
        $castRoute = $this->newCastRoute();
        $generator = $castRoute->getGenerator();
        $this->expectException(CastRouteException::class);
        $this->expectExceptionMessage("No such handler: 'NoSuchAction'");
        $generator->generate('NoSuchAction');
    }

    public function testGenerate_missingRequiredValue() : void
    {
        $castRoute = $this->newCastRoute();
        $generator = $castRoute->getGenerator();
        $this->expectException(CastRouteException::class);
        $this->expectExceptionMessage('Missing {id} value for route to GetUserAction');
        $generator->generate('GetUserAction');
    }
}
