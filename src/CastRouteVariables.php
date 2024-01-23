<?php
declare(strict_types=1);

namespace CastRoute;

class CastRouteVariables
{
    /**
     * @var array<string, string>
     */
    private array $strtr = [];

    /**
     * @var array<string, string>
     */
    private array $types = [
        'array' => '[^/]+',
        'bool' => '0|f|false|n|no|1|t|true|y|yes',
        'float' => '\d+\.?\d+',
        'int' => '\d+',
        'mixed' => '[^/]+',
        'string' => '[^/]+',
    ];

    public function __construct()
    {
        $this->types = array_replace($this->types, $this->modTypes());

        foreach ($this->types as $type => $regex) {
            $this->strtr[":{$type}}"] = ":{$regex}}";
        }
    }

    /**
     * @return array<string, string>
     */
    protected function modTypes() : array
    {
        return [];
    }

    public function fixRoute(string $route) : string
    {
        return strtr($route, $this->strtr);
    }

    /**
     * @param array<string, string> $variables
     * @return array<string, mixed>
     */
    public function fixVariables(string $route, array $variables) : array
    {
        preg_match_all(
            '/{([A-Za-z_][A-Za-z0-9_]*):([A-Za-z_][A-Za-z0-9_]*)}/',
            $route,
            $matches,
            PREG_SET_ORDER,
        );

        foreach ($matches as $match) {
            [$full, $name, $type] = $match;

            if (! array_key_exists($name, $variables)) {
                continue;
            }

            if (! method_exists($this, $type)) {
                continue;
            }

            $variables[$name] = $this->{$type}($variables[$name]);
        }

        return $variables;
    }

    /**
     * @return array<int, string|null>
     */
    protected function array(string $value) : array
    {
        return str_getcsv((string) $value);
    }

    protected function bool(string $value) : bool
    {
        if (in_array($value, ['1', 't', 'true', 'y', 'yes'])) {
            return true;
        }

        if (in_array($value, ['0', 'f', 'false', 'n', 'no'])) {
            return false;
        }

        return boolval($value);
    }

    protected function int(string $value) : int
    {
        return intval($value);
    }

    protected function float(string $value) : float
    {
        return floatval($value);
    }
}
