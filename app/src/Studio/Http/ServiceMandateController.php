<?php declare(strict_types=1);

namespace App\Studio\Http;

use App\Studio\Catalog\ServiceCatalog;
use Webkernel\HttpFoundation\Response;
use Webkernel\View\View;

final class ServiceMandateController
{
    public static function show(string $slug): Response
    {
        $service = ServiceCatalog::all()[$slug] ?? null;
        if (! is_array($service) || ($service['type'] ?? null) !== 'mandate') {
            return new Response('Not found', 404, ['Content-Type' => 'text/plain; charset=UTF-8']);
        }

        $imageReferences = [
            'brand' => 'brand-kit-complete',
            'digital' => 'website-build',
            'systems' => 'crm-client-portal',
            'intel' => 'ops-audit',
            'recruit' => 'remote-talent-search',
            'sales' => 'revenue-desk-pilot',
        ];
        $imageReference = ServiceCatalog::all()[$imageReferences[$service['cat']] ?? ''] ?? [];

        $service['introduction'] = $service['scope'];
        $service['sections'] = [
            [
                'title' => 'Deliverables',
                'items' => array_values(array_filter(array_map('trim', explode('·', $service['deliverables'])), static fn (string $item): bool => $item !== '')),
            ],
            [
                'title' => 'Engagement terms',
                'paragraphs' => [$service['terms']],
            ],
        ];
        $service['image_source'] = $imageReference['image_source'] ?? '';
        $service['image'] = $imageReference['image'] ?? '';
        $service['image_alt'] = $imageReference['image_alt'] ?? $service['name'];
        $service['href'] = simple_site('studio', 'services/'.$slug);

        $html = View::make('studio::pages.service-mandate', ['service' => $service])->render();

        return new Response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }
}
