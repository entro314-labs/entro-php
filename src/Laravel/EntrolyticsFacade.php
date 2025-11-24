<?php

declare(strict_types=1);

namespace Entrolytics\Laravel;

use Illuminate\Support\Facades\Facade;

/**
 * @method static bool track(array $params)
 * @method static bool pageView(array $params)
 * @method static bool identify(array $params)
 *
 * @see \Entrolytics\Client
 */
class EntrolyticsFacade extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'entrolytics';
    }
}
