<?php declare(strict_types=1);

namespace Webkernel\HttpErrorPages\Providers;

use Webkernel\ServiceProvider;

final class HttpErrorPagesProvider extends ServiceProvider
{
    public const array CONFIG = [__DIR__.'/../../config/http-error-pages.php'];
    public const array VIEWS = [__DIR__.'/../../resources/views'];
}
