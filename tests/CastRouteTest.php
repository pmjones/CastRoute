<?php
declare(strict_types=1);

namespace CastRoute;

use FastRoute\RouteParser;
use FastRoute\DataGenerator;

class CastRouteTest extends CastRouteTestCase
{
    public function testCached() : void
    {
        // remove existing cache
        $this->removeCache();
        $this->assertFalse(file_exists($this->cacheFile));

        // initialize data
        $castRoute = $this->newCastRoute(cacheFile: $this->cacheFile);

        // works as normal?
        $expect = [CastRouteDispatcher::FOUND, 'GetUserAction', ['id' => 88]];
        $actual = $castRoute->getDispatcher()->dispatch('GET', '/user/88');
        $this->assertSame($expect, $actual);

        // does the cache exist?
        $this->assertTrue(file_exists($this->cacheFile));

        // works from cache?
        $castRoute = $this->newCastRoute(cacheFile: $this->cacheFile);
        $expect = [CastRouteDispatcher::FOUND, 'GetUserAction', ['id' => 88]];
        $actual = $castRoute->getDispatcher()->dispatch('GET', '/user/88');
        $this->assertSame($expect, $actual);
    }

    public function testInvalidCache() : void
    {
        // remove existing cache
        $this->removeCache();
        $this->assertFalse(file_exists($this->cacheFile));

        // fake a bad cache
        file_put_contents($this->cacheFile, '<?php return [];');
        $this->assertTrue(file_exists($this->cacheFile));

        // build from a bad cache
        $castRoute = $this->newCastRoute(cacheFile: $this->cacheFile);

        // should blow up
        $this->expectException(CastRouteException::class);
        $this->expectExceptionMessage("Invalid cache file: {$this->cacheFile}");
        $dispatcher = $castRoute->getDispatcher();
    }
}
