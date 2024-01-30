# CastRoute

CastRoute decorates and extends [FastRoute](https://github.com/nikic/FastRoute) to add:

- route generation (aka reverse routing)
- automated casting of route variables to specified types
- a data structure for dumping routes

The first is an often-repeated feature request for FastRoute; the second and third are replicas of functionality found in [AutoRoute](https://github.com/nikic/FastRoute), but without using reflection as AutoRoute does.

CastRoute is not quite a drop-in replacement for FastRoute, because it uses a different approach to instantiating the dispatcher, but other than that it is identical to FastRoute. All pre-existing route definitions and dispatch handling will work without modification.

## Getting Started

### Route Definition

Instead of calling `\FastRoute\simpleDispatcher()` or `\FastRoute\cachedDispatcher()`, use the _CastRoute_ container object to collect routes. Your existing route collector callable for FastRoute will continue to work as-is when you pass it via the `$routes` parameter.

```php
use CastRoute\CastRoute;

$castRoute = new CastRoute(
    routes: function ($r) {
        $r->addRoute('GET', '/users', 'get_all_users_handler');
        $r->addRoute('GET', '/user/{id:\d+}', 'get_user_handler');
        $r->addRoute('GET', '/articles/{id:\d+}[/{title}]', 'get_article_handler');
    }),
);
```

CastRoute adds the ability to specify variable types in the route itself. For example, the route variables above might be specified like this instead (note that `:\d+` has been replaced by `:int`):

```php
use CastRoute\CastRoute;

$castRoute = new CastRoute(
    routes: function ($r) {
        $r->addRoute('GET', '/users', 'get_all_users_handler');
        $r->addRoute('GET', '/user/{id:int}', 'get_user_handler');
        $r->addRoute('GET', '/articles/{id:int}[/{title}]', 'get_article_handler');
    }),
);
```

CastRoute supports these variable types (matching these regexes) by default:

- `array`: `[^/]+`
- `bool`: `0|f|false|n|no|1|t|true|y|yes`
- `float`: `\d+\.?\d+`
- `int`: `\d+`
- `string`: `[^/]+`

Variables specified in this way will be recast at `dispatch()` time via an instance of _CastRouteVariables_:

- `array` values are converted using `str_getcsv()`
- `bool` values are cast to `(bool)`
- `float` values are cast to `(float)`
- `int` values are cast to `(int)`

All other values remain as strings.

You can create your own variable type regexes and casting/conversion by extending _CastRouteVariables_; see below for more information.

### Route Dispatching

Dispatching to get back route information is the same as with FastRoute. First get the dispatcher from the _CastRoute_ container, then work with it just as you would with FastRoute. The following example is taken directly from the FastRoute documentation:

```php
$dispatcher = $castRoute->getDispatcher();

// Fetch method and URI from somewhere
$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

// Strip query string (?foo=bar) and decode URI
if (false !== $pos = strpos($uri, '?')) {
    $uri = substr($uri, 0, $pos);
}

$uri = rawurldecode($uri);

$routeInfo = $dispatcher->dispatch($httpMethod, $uri);
switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::NOT_FOUND:
        // ... 404 Not Found
        break;
    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        $allowedMethods = $routeInfo[1];
        // ... 405 Method Not Allowed
        break;
    case FastRoute\Dispatcher::FOUND:
        $handler = $routeInfo[1];
        $vars = $routeInfo[2];
        // ... call $handler with $vars
        break;
}
```

### Route Generation

If a route handler specification is a string, you can generate a route path using that handler name. (Route handlers that are not strings cannot be used with route generation.)

To generate a route, get the _CastRouteGenerator_ out of the _CastRoute_ container, then call `generate()` with the handler string and the variables to interpolate into the path:

```php
$generator = $castRoute->getGenerator();

$path = $generator->generate('get_article_handler', [
    'id' => '88',
    'title' => 'the-article-slug'
]);

assert($path === '/articles/88/the-article-slug');
```

### Route Dumping

CastRoute maintains a data structure of all routes with their HTTP methods, paths, and handlers.

To dump this data structure and see all the routes, get the _CastRouteHandler_ object from the _CastRoute_ container, and call its `getUrls()` method. You can then use that data structure to print out a dump of all routes. For example, this script ...

```php
// define routes
$castRoute = new CastRoute(
    routes: function ($r) {
        // user
        $r->addRoute('GET', '/user/{id:int}', 'GetUserAction');
        $r->patch('/user/{id:int}', 'EditUserAction');
        $r->post('/user', 'PostUserAction');

        // article
        $r->get('/article/{id:int}', 'GetArticleAction');
        $r->post('/article', 'PostArticleAction');
        $r->patch('/article/{id:int}', 'PatchArticleAction');
    },
);

// dump routes
$urls = $castRoute->getRouteHandlers()->getUrls();

foreach ($urls as $path => $methodHandler) {
    echo $path . PHP_EOL;
    foreach ($methodHandler as $method => $handler) {
        echo "    {$method}: {$handler}" . PHP_EOL;
    }
    echo PHP_EOL;
}
```

... will generate output that looks something like this:

```
/article
    POST: PostArticleAction

/article/{id:int}
    GET: GetArticleAction
    HEAD: GetArticleAction
    PATCH: PatchArticleAction

/user
    POST: PostUserAction

/user/{id:int}
    GET: GetUserAction
    HEAD: GetUserAction
    PATCH: EditUserAction
```

## Customizing CastRoute

### Construction Options

Whereas FastRoute uses an `$options` array, the _CastRoute_ container uses separate constructor parameters with the same names as the FastRoute `$options` array keys:

- `string $routeParser = \FastRoute\RouteParser\Std::class`: an alternative RouteParser implementation
- `string $dataGenerator = \FastRoute\DataGenerator\GroupCountBased::class`: an alternative DataGenerator implementation
- `string $dispatcher = \FastRoute\Dispatcher\GroupCountBased::class`: an alternative Dispatcher implementation; this will be decorated by the _CastRouteDispatcher_ instance
- `?string $cacheFile = null`: The path to the cache file. When null, caching is not used.
- `bool $cacheDisabled = false`: When true, caching will *not* be used, even when a `$cacheFile` has been specified.

CastRoute adds two more options:

- `string $routeCollector = \CastRoute\CastRouteCollector::class`: an alternative _CastRouteCollector_ implementation
- `string $routeVariables = \CastRoute\CastRouteVariables::class`: an alternative _CastRouteVariables_ implementation

### Extending _CastRouteCollector_

You may wish to extend _CastRouteCollector_ to implement convenience methods. For example, the following implementation adds a `map()` method that automatically picks up the `$httpMethod` from the handler class name:

```php
class CustomRouteCollector extends CastRouteCollector
{
    public function map(string $route, string $handlerClass) : void
    {
        $parts = explode('\\', $handlerClass);
        preg_match('/^([A-Z][a-z]+).*/', end($parts), $matches);
        $this->addRoute(strtoupper($matches[1]), $route, $handlerClass);
    }
}
```

You can then use your alternative route collector when instantiating the _CastRoute_ container:

```php
use CastRoute\CastRoute;
use CustomRouteCollector;

$castRoute = new CastRoute(
    routeCollector: CustomRouteCollector::class,
    routes: function (CustomRouteCollector $r) {
        $r->map('/user/{id:int}', GetUserAction::class);
    }),
);
```

### Extending _CastRouteVariables_

You may wish to extend _CastRouteVariables_ to implement additional variable type regexes and casting/conversion. For example, the following implementation adds a `uuid` type regex and corresponding `uuid()` conversion method:

```php
use Ramsey\Uuid\Uuid;

class CustomRouteVariables extends CastRouteVariables
{
    protected function modTypes() : array
    {
        return [
            'uuid' => '[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}',
        ];
    }

    public function uuid(string $value) : Uuid
    {
        return Uuid::fromString($value);
    }
}
```

You can now use a `uuid` type in your route definitions ...

```php
use CastRoute\CastRoute;
use CustomRouteVariables;

$castRoute = new CastRoute(
    routeVariables: CustomRouteVariables::class,
    routes: function ($r) {
        $r->get('/resource/{resourceId:uuid}');
    },
);
```

... and on `dispatch()` the returned `resourceId` variable will be a _Uuid_ object.

Implementation notes:

- Override `modTypes()` to return an array of `'type' => 'regex'`; these will combined into the default types using `array_replace()`.

- The type name doubles as a method name; the method must take a `string $value` parameter, and may return anything. If there is no method for the type name, the `$value` will remain a string.
