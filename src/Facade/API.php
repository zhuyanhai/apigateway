<?php

namespace Zyh\ApiGateway\Facade;

use Zyh\ApiGateway\Http\InternalRequest;
use Illuminate\Support\Facades\Facade;

class API extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'apigateway.dispatcher';
    }

    /**
     * Bind an exception handler.
     *
     * @param callable $callback
     *
     * @return void
     */
    public static function error(callable $callback)
    {
        return static::$app['apigateway.exception']->register($callback);
    }

    /**
     * Get the authenticator.
     *
     * @return \Zyh\ApiGateway\Auth\Auth
     */
    public static function auth()
    {
        return static::$app['apigateway.auth'];
    }

    /**
     * Get the authenticated user.
     *
     * @return \Illuminate\Auth\GenericUser|\Illuminate\Database\Eloquent\Model
     */
    public static function user()
    {
        return static::$app['apigateway.auth']->user();
    }

    /**
     * Determine if a request is internal.
     *
     * @return bool
     */
    public static function internal()
    {
        return static::$app['apigateway.router']->getCurrentRequest() instanceof InternalRequest;
    }

    /**
     * Get the API router instance.
     *
     * @return \Zyh\ApiGateway\Routing\Router
     */
    public static function router()
    {
        return static::$app['apigateway.router'];
    }
}
