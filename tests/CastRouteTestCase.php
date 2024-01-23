<?php
declare(strict_types=1);

namespace CastRoute;

use FastRoute\RouteParser;
use FastRoute\DataGenerator;

abstract class CastRouteTestCase extends \PHPUnit\Framework\TestCase
{
    protected string $cacheFile;

    protected function setUp() : void
    {
        $this->cacheFile = __DIR__ . DIRECTORY_SEPARATOR . 'castroute.cache.php';
    }

    protected function tearDown() : void
    {
        $this->removeCache();
    }

    protected function removeCache() : void
    {
        if (file_exists($this->cacheFile)) {
            unlink($this->cacheFile);
        }
    }

    protected function newCastRoute(string $cacheFile = null) : CastRoute
    {
        return new CastRoute(
            routeCollector: CustomRouteCollector::class,
            routeVariables: CustomRouteVariables::class,
            cacheFile: $cacheFile,
            routes: function ($r) {
                $r->addRoute('GET', '/user/{id:int}', 'GetUserAction');
                $r->patch('/user/{id:int}', 'EditUserAction');
                $r->map(
                    '/archive/{username}[/{year:year}[/{month:month}[/{day:day}]]]',
                    'GetArchiveAction',
                );
            },
        );
    }
}
