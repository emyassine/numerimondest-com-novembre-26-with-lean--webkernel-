<?php

namespace Webkernel\Platform\Actions\Exports\Http\Controllers;

use Webkernel\Platform\Actions\Exports\Enums\Contracts\ExportFormat as ExportFormatInterface;
use Webkernel\Platform\Actions\Exports\Enums\ExportFormat;
use Webkernel\Platform\Actions\Exports\Exporter;
use Webkernel\Platform\Actions\Exports\Models\Export;
use \Webkernel\HttpFoundation\Request;
use Webkernel\Utilities\Arr;
use \Webkernel\Accounts\Gate;
use Symfony\Component\HttpFoundation\Response;

class DownloadExport
{
    public function __invoke(Request $request, Export $export): Response
    {
        abort_unless(auth(
            $request->hasValidSignature(absolute: false)
                ? $request->query('authGuard')
                : null,
        )->check(), 401);

        $user = auth(
            $request->hasValidSignature(absolute: false)
                ? $request->query('authGuard')
                : null,
        )->user();

        $exportPolicy = Gate::getPolicyFor($export::class);

        if (filled($exportPolicy) && method_exists($exportPolicy, 'view')) {
            Gate::forUser($user)->authorize('view', Arr::wrap($export));
        } else {
            abort_unless($export->user()->is($user), 403);
        }

        $format = $this->resolveFormatFromRequest($request);

        abort_unless($format !== null, 404);

        if (! is_a($export->exporter, Exporter::class, allow_string: true)) { /** @phpstan-ignore function.alreadyNarrowedType */
            return $format->getDownloader()($export);
        }

        return $export->exporter::getDownloader($format)($export);
    }

    protected function resolveFormatFromRequest(Request $request): ?ExportFormatInterface
    {
        return ExportFormat::tryFrom($request->query('format'));
    }
}
