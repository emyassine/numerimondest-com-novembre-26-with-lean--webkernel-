<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\HttpFoundation\Resources\Json;

use ArrayAccess;
use \Webkernel\Container\Container;
use \Webkernel\Route\Contracts\UrlRoutable;
use \Webkernel\Utilities\Contracts\Arrayable;
use \Webkernel\Utilities\Contracts\Responsable;
use \Webkernel\ORM\JsonEncodingException;
use \Webkernel\HttpFoundation\JsonResponse;
use \Webkernel\HttpFoundation\Request;
use \Webkernel\HttpFoundation\Resources\Attributes\PreserveKeys;
use \Webkernel\HttpFoundation\Resources\ConditionallyLoadsAttributes;
use \Webkernel\HttpFoundation\Resources\DelegatesToResource;
use JsonException;
use JsonSerializable;
use ReflectionClass;

class JsonResource implements ArrayAccess, JsonSerializable, Responsable, UrlRoutable
{
    use ConditionallyLoadsAttributes, DelegatesToResource;

    /**
     * The resource instance.
     *
     * @var mixed
     */
    public $resource;

    /**
     * The additional data that should be added to the top-level resource array.
     *
     * @var array
     */
    public $with = [];

    /**
     * The additional metadata that should be added to the resource response.
     *
     * Added during response construction by the developer.
     *
     * @var array
     */
    public $additional = [];

    /**
     * The "data" wrapper that should be applied.
     *
     * @var string|null
     */
    public static $wrap = 'data';

    /**
     * Whether to force wrapping even if the $wrap key exists in underlying resource data.
     *
     * @var bool
     */
    public static bool $forceWrapping = false;

    /**
     * Create a new resource instance.
     *
     * @param  mixed  $resource
     */
    public function __construct($resource)
    {
        $this->resource = $resource;
    }

    /**
     * Create a new resource instance.
     *
     * @param  mixed  ...$parameters
     * @return static
     */
    public static function make(...$parameters)
    {
        return new static(...$parameters);
    }

    /**
     * Create a new anonymous resource collection.
     *
     * @param  mixed  $resource
     * @return \Webkernel\HttpFoundation\Resources\Json\AnonymousResourceCollection
     */
    public static function collection($resource)
    {
        return tap(static::newCollection($resource), function ($collection) {
            if (! array_key_exists(static::class, static::$cachedPreserveKeysAttributes)) {
                static::$cachedPreserveKeysAttributes[static::class] = (new ReflectionClass(static::class))->getAttributes(PreserveKeys::class) !== [];
            }

            if (static::$cachedPreserveKeysAttributes[static::class]) {
                $collection->preserveKeys = true;
            } elseif (property_exists(static::class, 'preserveKeys')) {
                $collection->preserveKeys = (new static([]))->preserveKeys === true;
            }
        });
    }

    /**
     * Create a new resource collection instance.
     *
     * @param  mixed  $resource
     * @return \Webkernel\HttpFoundation\Resources\Json\AnonymousResourceCollection
     */
    protected static function newCollection($resource)
    {
        return new AnonymousResourceCollection($resource, static::class);
    }

    /**
     * Resolve the resource to an array.
     *
     * @param  \Webkernel\HttpFoundation\Request|null  $request
     * @return array
     */
    public function resolve($request = null)
    {
        $data = $this->resolveResourceData(
            $request ?: $this->resolveRequestFromContainer()
        );

        if ($data instanceof Arrayable) {
            $data = $data->toArray();
        } elseif ($data instanceof JsonSerializable) {
            $data = $data->jsonSerialize();
        }

        return $this->filter((array) $data);
    }

    /**
     * Transform the resource into an array.
     *
     * @param  \Webkernel\HttpFoundation\Request  $request
     * @return array|\Webkernel\Utilities\Contracts\Arrayable|\JsonSerializable
     */
    public function toAttributes(Request $request)
    {
        if (property_exists($this, 'attributes')) {
            return $this->attributes;
        }

        return $this->toArray($request);
    }

    /**
     * Resolve the resource data to an array.
     *
     * @param  \Webkernel\HttpFoundation\Request  $request
     * @return array
     */
    public function resolveResourceData(Request $request)
    {
        return $this->toAttributes($request);
    }

    /**
     * Transform the resource into an array.
     *
     * @param  \Webkernel\HttpFoundation\Request  $request
     * @return array|\Webkernel\Utilities\Contracts\Arrayable|\JsonSerializable
     */
    public function toArray(Request $request)
    {
        if (is_null($this->resource)) {
            return [];
        }

        return is_array($this->resource)
            ? $this->resource
            : $this->resource->toArray();
    }

    /**
     * Convert the resource to JSON.
     *
     * @param  int  $options
     * @return string
     *
     * @throws \Webkernel\ORM\JsonEncodingException
     */
    public function toJson($options = 0)
    {
        try {
            $json = json_encode($this->jsonSerialize(), $options | JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw JsonEncodingException::forResource($this, $e->getMessage());
        }

        return $json;
    }

    /**
     * Convert the resource to pretty print formatted JSON.
     *
     * @param  int  $options
     * @return string
     *
     * @throws \Webkernel\ORM\JsonEncodingException
     */
    public function toPrettyJson(int $options = 0)
    {
        return $this->toJson(JSON_PRETTY_PRINT | $options);
    }

    /**
     * Get any additional data that should be returned with the resource array.
     *
     * @param  \Webkernel\HttpFoundation\Request  $request
     * @return array
     */
    public function with(Request $request)
    {
        return $this->with;
    }

    /**
     * Add additional metadata to the resource response.
     *
     * @param  array  $data
     * @return $this
     */
    public function additional(array $data)
    {
        $this->additional = $data;

        return $this;
    }

    /**
     * Get the JSON serialization options that should be applied to the resource response.
     *
     * @return int
     */
    public function jsonOptions()
    {
        return 0;
    }

    /**
     * Customize the response for a request.
     *
     * @param  \Webkernel\HttpFoundation\Request  $request
     * @param  \Webkernel\HttpFoundation\JsonResponse  $response
     * @return void
     */
    public function withResponse(Request $request, JsonResponse $response)
    {
        //
    }

    /**
     * Resolve the HTTP request instance from container.
     *
     * @return \Webkernel\HttpFoundation\Request
     */
    protected function resolveRequestFromContainer()
    {
        return Container::getInstance()->make('request');
    }

    /**
     * Set the string that should wrap the outer-most resource array.
     *
     * @param  string  $value
     * @return void
     */
    public static function wrap($value)
    {
        static::$wrap = $value;
    }

    /**
     * Disable wrapping of the outer-most resource array.
     *
     * @return void
     */
    public static function withoutWrapping()
    {
        static::$wrap = null;
    }

    /**
     * Transform the resource into an HTTP response.
     *
     * @param  \Webkernel\HttpFoundation\Request|null  $request
     * @return \Webkernel\HttpFoundation\JsonResponse
     */
    public function response($request = null)
    {
        return $this->toResponse(
            $request ?: $this->resolveRequestFromContainer()
        );
    }

    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Webkernel\HttpFoundation\Request  $request
     * @return \Webkernel\HttpFoundation\JsonResponse
     */
    public function toResponse($request)
    {
        return (new ResourceResponse($this))->toResponse($request);
    }

    /**
     * Prepare the resource for JSON serialization.
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->resolve($this->resolveRequestFromContainer());
    }

    /**
     * Flush the resource's global state.
     *
     * @return void
     */
    public static function flushState()
    {
        static::$wrap = 'data';
        static::$forceWrapping = false;
    }
}
