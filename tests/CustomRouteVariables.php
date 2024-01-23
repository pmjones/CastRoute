<?php
declare(strict_types=1);

namespace CastRoute;

class CustomRouteVariables extends CastRouteVariables
{
    protected function modTypes() : array
    {
        return [
            'year' => '\d{4}',
            'month' => '0[1-9]|1[0-2]',
            'day' => '0[1-9]|[12][0-9]|3[0-1]',
        ];
    }
}
