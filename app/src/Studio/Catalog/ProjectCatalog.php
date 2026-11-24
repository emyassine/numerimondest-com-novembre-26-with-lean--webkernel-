<?php declare(strict_types=1);

namespace App\Studio\Catalog;

use RuntimeException;

final class ProjectCatalog
{
    /** @return array<string, array<string, mixed>> */
    public static function all(): array
    {
        static $projects = null;

        if (\is_array($projects)) {
            return $projects;
        }

        $projectFiles = glob(base_path('app/resources/data/studio/projects/*.php')) ?: [];
        sort($projectFiles, SORT_NATURAL);

        $projects = [];

        foreach ($projectFiles as $projectFile) {
            $project = require $projectFile;

            if (! is_array($project) || ! is_string($project['slug'] ?? null)) {
                throw new RuntimeException('Invalid Studio project data in '.$projectFile);
            }

            $projects[$project['slug']] = $project;
        }

        return $projects;
    }

    /** @return array<string, mixed> */
    public static function find(string $slug): array
    {
        $project = self::all()[$slug] ?? null;

        if (! is_array($project)) {
            throw new RuntimeException('Studio project not found: '.$slug);
        }

        return $project;
    }

    /** @return array<string, array<string, mixed>> */
    public static function initiatives(): array
    {
        return array_filter(
            self::all(),
            static fn (array $project): bool => ($project['kind'] ?? 'initiative') !== 'service',
        );
    }
}
