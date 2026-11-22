<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.
// Adapted from Laravel; see LICENSE-LARAVEL.md.

namespace Webkernel\Filesystem\Aws;

use Webkernel\Filesystem\FilesystemAdapter;

use Aws\S3\S3Client;
use Webkernel\Utilities\Traits\Conditionable;
use Webkernel\Filesystem\Storage\FilesystemAdapter as StorageAdapter;
use Webkernel\Filesystem\Storage\FilesystemOperator;

class S3Adapter extends FilesystemAdapter
{
    use Conditionable;

    /**
     * The AWS S3 client.
     *
     * @var \Aws\S3\S3Client
     */
    protected $client;

    /**
     * Create a new AwsS3V3FilesystemAdapter instance.
     *
     * @param  \Webkernel\Filesystem\Storage\FilesystemOperator  $driver
     * @param  \Webkernel\Filesystem\Storage\FilesystemAdapter  $adapter
     * @param  array  $config
     * @param  \Aws\S3\S3Client  $client
     */
    public function __construct(FilesystemOperator $driver, StorageAdapter $adapter, array $config, S3Client $client)
    {
        $config['directory_separator'] = '/';

        parent::__construct($driver, $adapter, $config);

        $this->client = $client;
    }

    /**
     * Get the URL for the file at the given path.
     *
     * @param  string  $path
     * @return string
     *
     * @throws \RuntimeException
     */
    public function url($path)
    {
        // If an explicit base URL has been set on the disk configuration then we will use
        // it as the base URL instead of the default path. This allows the developer to
        // have full control over the base path for this filesystem's generated URLs.
        if (isset($this->config['url'])) {
            return $this->concatPathToUrl($this->config['url'], $this->prefixer->prefixPath($path));
        }

        return $this->client->getObjectUrl(
            $this->config['bucket'], $this->prefixer->prefixPath($path)
        );
    }

    /**
     * Determine if temporary URLs can be generated.
     *
     * @return bool
     */
    public function providesTemporaryUrls()
    {
        return true;
    }

    /**
     * Determine if temporary upload URLs can be generated.
     *
     * @return bool
     */
    public function providesTemporaryUploadUrls()
    {
        return true;
    }

    /**
     * Get a temporary URL for the file at the given path.
     *
     * @param  string  $path
     * @param  \DateTimeInterface  $expiration
     * @param  array  $options
     * @return string
     */
    public function temporaryUrl($path, $expiration, array $options = [])
    {
        $command = $this->client->getCommand('GetObject', array_merge([
            'Bucket' => $this->config['bucket'],
            'Key' => $this->prefixer->prefixPath($path),
        ], $options));

        $uri = $this->client->createPresignedRequest(
            $command, $expiration, $options
        )->getUri();

        // If an explicit base URL has been set on the disk configuration then we will use
        // it as the base URL instead of the default path. This allows the developer to
        // have full control over the base path for this filesystem's generated URLs.
        if (isset($this->config['temporary_url'])) {
            $uri = $this->replaceBaseUrl($uri, $this->config['temporary_url']);
        }

        return (string) $uri;
    }

    /**
     * Get a temporary upload URL for the file at the given path.
     *
     * @param  string  $path
     * @param  \DateTimeInterface  $expiration
     * @param  array  $options
     * @return array
     */
    public function temporaryUploadUrl($path, $expiration, array $options = [])
    {
        $command = $this->client->getCommand('PutObject', array_merge([
            'Bucket' => $this->config['bucket'],
            'Key' => $this->prefixer->prefixPath($path),
        ], $options));

        $signedRequest = $this->client->createPresignedRequest(
            $command, $expiration, $options
        );

        $uri = $signedRequest->getUri();

        // If an explicit base URL has been set on the disk configuration then we will use
        // it as the base URL instead of the default path. This allows the developer to
        // have full control over the base path for this filesystem's generated URLs.
        if (isset($this->config['temporary_url'])) {
            $uri = $this->replaceBaseUrl($uri, $this->config['temporary_url']);
        }

        return [
            'url' => (string) $uri,
            'headers' => $signedRequest->getHeaders(),
        ];
    }

    /**
     * Get the underlying S3 client.
     *
     * @return \Aws\S3\S3Client
     */
    public function getClient()
    {
        return $this->client;
    }
}
