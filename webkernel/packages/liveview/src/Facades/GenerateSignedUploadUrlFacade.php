<?php

namespace Webkernel\Liveview\Facades;

use Webkernel\Container\Facade;

/**
 * @internal
 *
 * @method static string forLocal()
 * @method static string forS3($file, $visibility = 'private')
 * @method static string signedRoute($name, $expiration, $parameters = [])
 *
 * @see \Webkernel\Liveview\Features\SupportFileUploads\GenerateSignedUploadUrl
 */
class GenerateSignedUploadUrlFacade extends Facade
{
    public static function getFacadeAccessor()
    {
        return \Webkernel\Liveview\Features\SupportFileUploads\GenerateSignedUploadUrl::class;
    }
}
