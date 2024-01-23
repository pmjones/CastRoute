<?php
declare(strict_types=1);

namespace CastRoute;

use FastRoute;

class CastRoute
{
    /** @var callable */
    protected mixed $routes;

    protected FastRoute\Dispatcher $fastRouteDispatcher;

    protected ?CastRouteDispatcher $castRouteDispatcher = null;

    protected ?CastRouteGenerator $routeGenerator = null;

    protected ?CastRouteHandlers $routeHandlers = null;

    protected ?CastRouteVariables $routeVariables = null;

    /**
     * @var array{
     *  routeParser: string,
     *  dataGenerator: string,
     *  dispatcher: string,
     *  routeCollector: string,
     *  routeVariables: string,
     *  cacheFile: ?string,
     *  cacheDisabled: bool,
     * }
     */
    protected array $options;

    public function __construct(
        callable $routes,
        string $routeParser = FastRoute\RouteParser\Std::class,
        string $dataGenerator = FastRoute\DataGenerator\GroupCountBased::class,
        string $dispatcher = FastRoute\Dispatcher\GroupCountBased::class,
        string $routeCollector = CastRouteCollector::class,
        string $routeVariables = CastRouteVariables::class,
        ?string $cacheFile = null,
        bool $cacheDisabled = false,
    ) {
        $this->routes = $routes;
        $this->options = [
            'routeParser' => $routeParser,
            'dataGenerator' => $dataGenerator,
            'dispatcher' => $dispatcher,
            'routeCollector' => $routeCollector,
            'routeVariables' => $routeVariables,
            'cacheFile' => $cacheFile,
            'cacheDisabled' => $cacheDisabled,
        ];
    }

    public function getDispatcher() : CastRouteDispatcher
    {
        if (! $this->castRouteDispatcher) {
            $this->build();
        }

        /** @var CastRouteDispatcher */
        return $this->castRouteDispatcher;
    }

    public function getGenerator() : CastRouteGenerator
    {
        if (! $this->routeGenerator) {
            $this->build();
        }

        /** @var CastRouteGenerator */
        return $this->routeGenerator;
    }

    public function getRouteHandlers() : CastRouteHandlers
    {
        if (! $this->routeHandlers) {
            $this->build();
        }

        /** @var CastRouteHandlers */
        return $this->routeHandlers;
    }

    public function getRouteVariables() : CastRouteVariables
    {
        if (! $this->routeVariables) {
            /** @var CastRouteVariables */
            $routeVariables = new $this->options['routeVariables']();
            $this->routeVariables = $routeVariables;
        }

        return $this->routeVariables;
    }

    protected function build() : void
    {
        if (
            ! $this->options['cacheDisabled']
            && $this->options['cacheFile']
            && file_exists($this->options['cacheFile'])
        ) {
            $this->buildCached();
            return;
        }

        $routeVariables = $this->getRouteVariables();
        $this->routeHandlers = new CastRouteHandlers($routeVariables);

        /** @var FastRoute\RouteParser */
        $routeParser = new $this->options['routeParser']();

        /** @var FastRoute\DataGenerator */
        $dataGenerator = new $this->options['dataGenerator']();

        /** @var CastRouteCollector */
        $routeCollector = new $this->options['routeCollector'](
            $routeParser,
            $dataGenerator,
            $this->routeHandlers,
            $routeVariables,
        );
        call_user_func($this->routes, $routeCollector);
        $cacheData = [
            'handlers' => $this->routeHandlers->getData(),
            'dispatch' => $routeCollector->getData(),
        ];

        if (! $this->options['cacheDisabled'] && $this->options['cacheFile']) {
            file_put_contents(
                $this->options['cacheFile'],
                '<?php return ' . var_export($cacheData, true) . ';',
            );
        }

        /** @var FastRoute\Dispatcher */
        $fastRouteDispatcher = new $this->options['dispatcher']($cacheData['dispatch']);
        $this->fastRouteDispatcher = $fastRouteDispatcher;
        $this->castRouteDispatcher = new CastRouteDispatcher(
            $this->fastRouteDispatcher,
            $this->routeHandlers,
        );
        $this->routeGenerator = new CastRouteGenerator($this->routeHandlers);
    }

    protected function buildCached() : void
    {
        $cacheData = (require $this->options['cacheFile']);

        if (
            ! is_array($cacheData)
            || ! is_array($cacheData['handlers'] ?? null)
            || ! is_array($cacheData['dispatch'] ?? null)
        ) {
            throw new CastRouteException(
                'Invalid cache file: ' . $this->options['cacheFile'],
            );
        }

        $this->routeHandlers = new CastRouteHandlers(
            $this->getRouteVariables(),
            $cacheData['handlers'],
        );
        $this->routeGenerator = new CastRouteGenerator($this->routeHandlers);

        /** @var FastRoute\Dispatcher */
        $fastRouteDispatcher = new $this->options['dispatcher']($cacheData['dispatch']);
        $this->fastRouteDispatcher = $fastRouteDispatcher;
        $this->castRouteDispatcher = new CastRouteDispatcher(
            $this->fastRouteDispatcher,
            $this->routeHandlers,
        );
        $this->routeGenerator = new CastRouteGenerator($this->routeHandlers);
    }
}
