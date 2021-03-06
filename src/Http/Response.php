<?php

namespace Zyh\ApiGateway\Http;

use ArrayObject;
use UnexpectedValueException;
use Illuminate\Http\JsonResponse;
use Zyh\ApiGateway\Event\ResponseIsMorphing;
use Zyh\ApiGateway\Event\ResponseWasMorphed;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Response as IlluminateResponse;
use Illuminate\Events\Dispatcher as EventDispatcher;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;

class Response extends IlluminateResponse
{
    /**
     * The exception that triggered the error response.
     *
     * @var \Exception
     */
    public $exception;

    /**
     * Array of registered formatters.
     *
     * @var array
     */
    protected static $formatters = [];

    /**
     * Event dispatcher instance.
     *
     * @var \Illuminate\Events\Dispatcher
     */
    protected static $events;

    /**
     * Create a new response instance.
     *
     * @param mixed                          $content
     * @param int                            $status
     * @param array                          $headers
     *
     * @return void
     */
    public function __construct($content, $status = 200, $headers = [])
    {
        parent::__construct($content, $status, $headers);
    }

    /**
     * Make an API response from an existing Illuminate response.
     *
     * @param \Illuminate\Http\Response $old
     *
     * @return \Zyh\ApiGateway\Http\Response
     */
    public static function makeFromExisting(IlluminateResponse $old)
    {
        $new = static::create($old->getOriginalContent(), $old->getStatusCode());

        $new->headers = $old->headers;

        return $new;
    }

    /**
     * Make an API response from an existing JSON response.
     *
     * @param \Illuminate\Http\JsonResponse $json
     *
     * @return \Zyh\ApiGateway\Http\Response
     */
    public static function makeFromJson(JsonResponse $json)
    {
        $new = static::create(json_decode($json->getContent(), true), $json->getStatusCode());

        $new->headers = $json->headers;

        return $new;
    }

    /**
     * Morph the API response to the appropriate format.
     *
     * @param string $format
     *
     * @return \Zyh\ApiGateway\Http\Response
     */
    public function morph($format = 'json')
    {
        $this->content = $this->getOriginalContent();

        $this->fireMorphingEvent();

        $formatter = static::getFormatter($format);

        $defaultContentType = $this->headers->get('Content-Type');

        $this->headers->set('Content-Type', $formatter->getContentType());

        $this->fireMorphedEvent();

        if ($this->content instanceof EloquentModel) {
            $this->content = $formatter->formatEloquentModel($this->content);
        } elseif ($this->content instanceof EloquentCollection) {
            $this->content = $formatter->formatEloquentCollection($this->content);
        } elseif (is_array($this->content) || $this->content instanceof ArrayObject || $this->content instanceof Arrayable) {
            $this->content = $formatter->formatArray($this->content);
        } else {
            $this->headers->set('Content-Type', $defaultContentType);
        }

        return $this;
    }

    /**
     * Fire the morphed event.
     *
     * @return void
     */
    protected function fireMorphedEvent()
    {
        if (! static::$events) {
            return;
        }

        static::$events->fire(new ResponseWasMorphed($this, $this->content));
    }

    /**
     * Fire the morphing event.
     *
     * @return void
     */
    protected function fireMorphingEvent()
    {
        if (! static::$events) {
            return;
        }

        static::$events->fire(new ResponseIsMorphing($this, $this->content));
    }

    /**
     * {@inheritdoc}
     */
    public function setContent($content)
    {
        // Attempt to set the content string, if we encounter an unexpected value
        // then we most likely have an object that cannot be type cast. In that
        // case we'll simply leave the content as null and set the original
        // content value and continue.
        try {
            return parent::setContent($content);
        } catch (UnexpectedValueException $exception) {
            $this->original = $content;

            return $this;
        }
    }

    /**
     * Set the event dispatcher instance.
     *
     * @param \Illuminate\Events\Dispatcher $events
     *
     * @return void
     */
    public static function setEventDispatcher(EventDispatcher $events)
    {
        static::$events = $events;
    }

    /**
     * Get the formatter based on the requested format type.
     *
     * @param string $format
     *
     * @throws \RuntimeException
     *
     * @return \Zyh\ApiGateway\Http\Response\Format\Format
     */
    public static function getFormatter($format)
    {
        if (! static::hasFormatter($format)) {
            throw new NotAcceptableHttpException('Unable to format response according to Accept header.');
        }

        return static::$formatters[$format];
    }

    /**
     * Determine if a response formatter has been registered.
     *
     * @param string $format
     *
     * @return bool
     */
    public static function hasFormatter($format)
    {
        return isset(static::$formatters[$format]);
    }

    /**
     * Set the response formatters.
     *
     * @param array $formatters
     *
     * @return void
     */
    public static function setFormatters(array $formatters)
    {
        static::$formatters = $formatters;
    }

    /**
     * Add a response formatter.
     *
     * @param string                                 $key
     * @param \Zyh\ApiGateway\Http\Response\Format\Format $formatter
     *
     * @return void
     */
    public static function addFormatter($key, $formatter)
    {
        static::$formatters[$key] = $formatter;
    }

    /**
     * Add a cookie to the response.
     *
     * @param \Symfony\Component\HttpFoundation\Cookie|mixed $cookie
     *
     * @return \Zyh\ApiGateway\Http\Response
     */
    public function cookie($cookie)
    {
        return $this->withCookie($cookie);
    }

    /**
     * Add a header to the response.
     *
     * @param string $key
     * @param string $value
     * @param bool   $replace
     *
     * @return \Zyh\ApiGateway\Http\Response
     */
    public function withHeader($key, $value, $replace = true)
    {
        return $this->header($key, $value, $replace);
    }

    /**
     * Set the response status code.
     *
     * @param int $statusCode
     *
     * @return \Zyh\ApiGateway\Http\Response
     */
    public function statusCode($statusCode)
    {
        return $this->setStatusCode($statusCode);
    }
}
