<?php

declare(strict_types=1);

/*
 * This file is part of the JsonSchema package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webkernel\Utilities\JsonSchema\Uri;

use stdClass;
use Webkernel\Utilities\JsonSchema\DraftIdentifiers;
use Webkernel\Utilities\JsonSchema\Exception\InvalidSchemaMediaTypeException;
use Webkernel\Utilities\JsonSchema\Exception\JsonDecodingException;
use Webkernel\Utilities\JsonSchema\Exception\ResourceNotFoundException;
use Webkernel\Utilities\JsonSchema\Uri\Retrievers\FileGetContents;
use Webkernel\Utilities\JsonSchema\Uri\Retrievers\UriRetrieverInterface;
use Webkernel\Utilities\JsonSchema\Uri\UriResolver;
use Webkernel\Utilities\JsonSchema\UriRetrieverInterface as BaseUriRetrieverInterface;
use Webkernel\Utilities\JsonSchema\Validator;

/**
 * Retrieves JSON Schema URIs
 *
 */
class UriRetriever implements BaseUriRetrieverInterface
{
    private const PACKAGE_SCHEME = 'package://';

    /**
     * @var array Map of URL translations
     */
    protected $translationMap = [
        // use local copies of the spec schemas
        '|^https?://json-schema.org/draft-(0[3467])/schema#?|' => 'package://dist/schema/json-schema-draft-$1.json',
        '|^https://json-schema.org/draft/2019-09/schema#?|' => 'package://dist/schema/json-schema-draft-2019-09.json'
    ];

    /**
     * @var array A list of endpoints for media type check exclusion
     */
    protected $allowedInvalidContentTypeEndpoints = [
        'http://json-schema.org/',
        'https://json-schema.org/'
    ];

    /**
     * @var null|UriRetrieverInterface
     */
    protected $uriRetriever = null;

    /**
     * @var array|object[]
     *
     * @see loadSchema
     */
    private $schemaCache = [];

    /**
     * Adds an endpoint to the media type validation exclusion list
     *
     * @param string $endpoint
     */
    public function addInvalidContentTypeEndpoint($endpoint)
    {
        $this->allowedInvalidContentTypeEndpoints[] = $endpoint;
    }

    /**
     * Guarantee the correct media type was encountered
     *
     * @param UriRetrieverInterface $uriRetriever
     * @param string                $uri
     *
     * @return bool|null
     */
    public function confirmMediaType(UriRetrieverInterface $uriRetriever, ?string $uri): ?bool
    {
        $contentType = $uriRetriever->getContentType();

        if (is_null($contentType)) {
            // Well, we didn't get an invalid one
            return null;
        }

        if (in_array($contentType, [Validator::SCHEMA_MEDIA_TYPE, 'application/json'])) {
            return null;
        }

        foreach ($this->allowedInvalidContentTypeEndpoints as $endpoint) {
            if (!\is_null($uri) && strpos($uri, $endpoint) === 0) {
                return true;
            }
        }

        throw new InvalidSchemaMediaTypeException(sprintf('Media type %s expected, but %s given', Validator::SCHEMA_MEDIA_TYPE, $contentType));
    }

    /**
     * Get a URI Retriever
     *
     * If none is specified, sets a default FileGetContents retriever and
     * returns that object.
     *
     * @return UriRetrieverInterface
     */
    public function getUriRetriever(): UriRetrieverInterface
    {
        $uriRetriever = $this->uriRetriever;

        if ($uriRetriever === null) {
            $uriRetriever = new FileGetContents();
            $this->setUriRetriever($uriRetriever);
        }

        return $uriRetriever;
    }

    /**
     * Resolve a schema based on pointer
     *
     * URIs can have a fragment at the end in the format of
     * #/path/to/object and we are to look up the 'path' property of
     * the first object then the 'to' and 'object' properties.
     *
     * @param object $jsonSchema JSON Schema contents
     * @param string $uri        JSON Schema URI
     *
     * @throws ResourceNotFoundException
     *
     * @return object JSON Schema after walking down the fragment pieces
     */
    public function resolvePointer(object $jsonSchema, string $uri): object
    {
        $resolver = new UriResolver();
        $parsed = $resolver->parse($uri);
        if (empty($parsed['fragment'])) {
            return $jsonSchema;
        }

        $path = explode('/', (string) $parsed['fragment']);
        while ($path) {
            $pathElement = (string) array_shift($path);
            if (!empty($pathElement)) {
                $pathElement = str_replace('~1', '/', $pathElement);
                $pathElement = str_replace('~0', '~', $pathElement);
                if (!empty($jsonSchema->$pathElement)) {
                    $jsonSchema = $jsonSchema->$pathElement;
                } else {
                    throw new ResourceNotFoundException(
                        'Fragment "' . $parsed['fragment'] . '" not found'
                        . ' in ' . $uri
                    );
                }

                if (!is_object($jsonSchema)) {
                    throw new ResourceNotFoundException(sprintf(
                        'Fragment part "%s" is no object in %s',
                        $pathElement,
                        $uri
                    ));
                }
            }
        }

        return $jsonSchema;
    }

    /**
     * {@inheritdoc}
     */
    public function retrieve(string $uri, ?string $baseUri = null, bool $translate = true): object
    {
        $resolver = new UriResolver();
        $resolvedUri = $fetchUri = $resolver->resolve($uri, $baseUri);

        //fetch URL without #fragment
        $arParts = $resolver->parse($resolvedUri);
        if (isset($arParts['fragment'])) {
            unset($arParts['fragment']);
            $fetchUri = $resolver->generate($arParts);
        }

        // apply URI translations
        if ($translate) {
            $fetchUri = $this->translate($fetchUri);
        }

        $jsonSchema = $this->loadSchema($fetchUri);

        // Detect dialect from the root schema's $schema keyword before
        // resolvePointer() may walk into a sub-schema.
        $dialect = isset($jsonSchema->{'$schema'}) ? rtrim($jsonSchema->{'$schema'}, '#') : null;
        $usesDollarId = !in_array($dialect, [DraftIdentifiers::Draft3->withoutFragment(), DraftIdentifiers::Draft4->withoutFragment()], true);

        // Use the JSON pointer if specified
        $jsonSchema = $this->resolvePointer($jsonSchema, $resolvedUri);

        if ($jsonSchema instanceof stdClass) {
            $idKeyword = $usesDollarId ? '$id' : 'id';
            $jsonSchema->{$idKeyword} = $resolvedUri;
        }

        return $jsonSchema;
    }

    /**
     * Fetch a schema from the given URI, json-decode it and return it.
     * Caches schema objects.
     *
     * @param string $fetchUri Absolute URI
     *
     * @return object JSON schema object
     */
    protected function loadSchema(string $fetchUri): object
    {
        if (isset($this->schemaCache[$fetchUri])) {
            return $this->schemaCache[$fetchUri];
        }

        $uriRetriever = $this->getUriRetriever();
        $contents = $uriRetriever->retrieve($fetchUri);
        $this->confirmMediaType($uriRetriever, $fetchUri);
        $jsonSchema = json_decode($contents);

        if (JSON_ERROR_NONE < $error = json_last_error()) {
            throw new JsonDecodingException($error);
        }

        $this->schemaCache[$fetchUri] = $jsonSchema;

        return $jsonSchema;
    }

    /**
     * Set the URI Retriever
     *
     * @param UriRetrieverInterface $uriRetriever
     *
     * @return $this for chaining
     */
    public function setUriRetriever(UriRetrieverInterface $uriRetriever)
    {
        $this->uriRetriever = $uriRetriever;

        return $this;
    }

    /**
     * Parses a URI into five main components
     *
     * @param string $uri
     *
     * @return array
     */
    public function parse(?string $uri): array
    {
        preg_match('|^(([^:/?#]+):)?(//([^/?#]*))?([^?#]*)(\?([^#]*))?(#(.*))?|', $uri, $match);

        $components = [];
        if (5 < count($match)) {
            $components =  [
                'scheme'    => $match[2],
                'authority' => $match[4],
                'path'      => $match[5]
            ];
        }

        if (7 < count($match)) {
            $components['query'] = $match[7];
        }

        if (9 < count($match)) {
            $components['fragment'] = $match[9];
        }

        return $components;
    }

    /**
     * Builds a URI based on n array with the main components
     *
     * @param array $components
     *
     * @return string
     */
    public function generate(array $components): string
    {
        $uri = $components['scheme'] . '://'
             . $components['authority']
             . $components['path'];

        if (array_key_exists('query', $components)) {
            $uri .= $components['query'];
        }

        if (array_key_exists('fragment', $components)) {
            $uri .= $components['fragment'];
        }

        return $uri;
    }

    /**
     * Resolves a URI
     *
     * @param string $uri     Absolute or relative
     * @param string $baseUri Optional base URI
     *
     * @return string
     */
    public function resolve(string $uri, ?string $baseUri = null): string
    {
        $components = $this->parse($uri);
        $path = $components['path'];

        if ((array_key_exists('scheme', $components)) && ('http' === $components['scheme'])) {
            return $uri;
        }

        $baseComponents = $this->parse($baseUri);
        $basePath = $baseComponents['path'];

        $baseComponents['path'] = UriResolver::combineRelativePathWithBasePath($path, $basePath);

        return $this->generate($baseComponents);
    }

    /**
     * @param string $uri
     *
     * @return bool
     */
    public function isValid(string $uri): bool
    {
        $components = $this->parse($uri);

        return !empty($components);
    }

    /**
     * Set a URL translation rule
     *
     * @param $from string
     * @param $to string
     */
    public function setTranslation(string $from, string $to): void
    {
        $this->translationMap[$from] = $to;
    }

    /**
     * Apply URI translation rules
     */
    public function translate(string $uri): string
    {
        foreach ($this->translationMap as $from => $to) {
            $uri = preg_replace($from, $to, $uri);
        }

        // translate references to local files within the json-schema package
        // a plain replacement, because the package path is data: a Windows path such as
        // C:\Users\4223627 would otherwise have its \4 read as a backreference
        if (0 === strpos($uri, self::PACKAGE_SCHEME)) {
            $uri = sprintf('file://%s/', realpath(__DIR__ . '/../../..')) . substr($uri, strlen(self::PACKAGE_SCHEME));
        }

        return $uri;
    }
}
