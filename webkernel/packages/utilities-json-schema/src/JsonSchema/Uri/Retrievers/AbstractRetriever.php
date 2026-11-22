<?php

declare(strict_types=1);

/**
 * JsonSchema
 *
 * @filesource
 */

namespace Webkernel\Utilities\JsonSchema\Uri\Retrievers;

/**
 * AbstractRetriever implements the default shared behavior
 * that all descendant Retrievers should inherit
 *
 */
abstract class AbstractRetriever implements UriRetrieverInterface
{
    /** Media content type returned by the retriever. */
    protected ?string $contentType = null;

    /**
     * {@inheritdoc}
     *
     * @see \Webkernel\Utilities\JsonSchema\Uri\Retrievers\UriRetrieverInterface::getContentType()
     */
    public function getContentType(): ?string
    {
        return $this->contentType;
    }
}
