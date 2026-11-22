<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\HttpFoundation\Resources\Json;

use Countable;
use \Webkernel\HttpFoundation\Request;
use \Webkernel\HttpFoundation\Resources\CollectsResources;
use \Webkernel\Database\Pagination\AbstractCursorPaginator;
use \Webkernel\Database\Pagination\AbstractPaginator;
use IteratorAggregate;

class ResourceCollection extends JsonResource implements Countable, IteratorAggregate
{
    use CollectsResources;

    /**
     * The resource that this resource collects.
     *
     * @var string
     */
    public $collects;

    /**
     * The mapped collection instance.
     *
     * @var \Webkernel\Utilities\Collection|null
     */
    public $collection;

    /**
     * Indicates if all existing request query parameters should be added to pagination links.
     *
     * @var bool
     */
    protected $preserveAllQueryParameters = false;

    /**
     * The query parameters that should be added to the pagination links.
     *
     * @var array|null
     */
    protected $queryParameters;

    /**
     * Create a new resource instance.
     *
     * @param  mixed  $resource
     */
    public function __construct($resource)
    {
        parent::__construct($resource);

        $this->resource = $this->collectResource($resource);
    }

    /**
     * Indicate that all current query parameters should be appended to pagination links.
     *
     * @return $this
     */
    public function preserveQuery()
    {
        $this->preserveAllQueryParameters = true;

        return $this;
    }

    /**
     * Specify the query string parameters that should be present on pagination links.
     *
     * @param  array  $query
     * @return $this
     */
    public function withQuery(array $query)
    {
        $this->preserveAllQueryParameters = false;

        $this->queryParameters = $query;

        return $this;
    }

    /**
     * Return the count of items in the resource collection.
     *
     * @return int
     */
    public function count(): int
    {
        return $this->collection->count();
    }

    /**
     * Transform the resource into a JSON array.
     *
     * @param  \Webkernel\HttpFoundation\Request  $request
     * @return array|\Webkernel\Utilities\Contracts\Arrayable|\JsonSerializable
     */
    #[\Override]
    public function toArray(Request $request)
    {
        if ($this->collection->first() instanceof JsonResource) {
            return $this->collection->map->resolve($request)->all();
        }

        return $this->collection->map->toArray($request)->all();
    }

    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Webkernel\HttpFoundation\Request  $request
     * @return \Webkernel\HttpFoundation\JsonResponse
     */
    public function toResponse($request)
    {
        if ($this->resource instanceof AbstractPaginator || $this->resource instanceof AbstractCursorPaginator) {
            return $this->preparePaginatedResponse($request);
        }

        return parent::toResponse($request);
    }

    /**
     * Create a paginate-aware HTTP response.
     *
     * @param  \Webkernel\HttpFoundation\Request  $request
     * @return \Webkernel\HttpFoundation\JsonResponse
     */
    protected function preparePaginatedResponse($request)
    {
        if ($this->preserveAllQueryParameters) {
            $this->resource->appends($request->query());
        } elseif (! is_null($this->queryParameters)) {
            $this->resource->appends($this->queryParameters);
        }

        return (new PaginatedResourceResponse($this))->toResponse($request);
    }
}
