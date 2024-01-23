<?php
declare(strict_types=1);

namespace CastRoute;

use FastRoute\Dispatcher;

/**
 * @phpstan-type stringy bool|int|float|string|null
 */
class CastRouteGenerator
{
    public function __construct(protected CastRouteHandlers $routeHandlers)
    {
    }

    /**
     * @param array<string, stringy> $values
     */
    public function generate(string $handler, array $values = []) : string
    {
        $url = '';

        /** @var ?mixed[] */
        $routeDatas = $this->routeHandlers->getRouteDatas($handler);

        if ($routeDatas === null) {
            throw new CastRouteException("No such handler: '{$handler}'");
        }

        $startAtSegment = 0;

        /** @var array<int, string|array{string, string}> */
        $required = array_shift($routeDatas);
        $this->generateRequired($handler, $required, $values, $url, $startAtSegment);

        while ($values && $routeDatas && $startAtSegment) {
            /** @var array<int, string|array{string, string}> */
            $optional = array_shift($routeDatas);
            $this->generateOptional(
                $handler,
                $optional,
                $values,
                $url,
                $startAtSegment,
            );
        }

        return $url;
    }

    /**
     * @param array<int, string|array{string, string}> $required
     * @param array<string, stringy> $values
     */
    protected function generateRequired(
        string $handler,
        array $required,
        array &$values,
        string &$url,
        int &$startAtSegment,
    ) : void
    {
        foreach ($required as $pos => $segment) {
            if (is_string($segment)) {
                $url .= $segment;
                continue;
            }

            list($name, $regex) = $segment;

            if (! array_key_exists($name, $values)) {
                throw new CastRouteException(
                    "Missing {{$name}} value for route to {$handler}",
                );
            }

            $url .= strval($values[$name]);
            unset($values[$name]);
        }

        $startAtSegment = count($required);
    }

    /**
     * @param array<int, string|array{string, string}> $optional
     * @param array<string, stringy> $values
     */
    protected function generateOptional(
        string $handler,
        array $optional,
        array &$values,
        string &$url,
        int &$startAtSegment,
    ) : void
    {
        $append = '';

        foreach ($optional as $pos => $segment) {
            if ($pos < $startAtSegment) {
                continue;
            }

            if (is_string($segment)) {
                $append .= $segment;
                continue;
            }

            list($name, $regex) = $segment;

            if (! array_key_exists($name, $values)) {
                // cannot complete this optional, nor any later optionals.
                $startAtSegment = 0;
                return;
            }

            $append .= strval($values[$name]);
            unset($values[$name]);
        }

        $url .= $append;
        $startAtSegment = count($optional);
    }
}
