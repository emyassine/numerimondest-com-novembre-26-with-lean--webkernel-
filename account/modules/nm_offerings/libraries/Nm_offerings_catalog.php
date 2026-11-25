<?php

defined('BASEPATH') or exit('No direct script access allowed');

use App\Studio\Catalog\OfferingCatalogValidator;
use App\Studio\Persistence\OfferingStore;

/** Shared access to the website's canonical SQLite catalogue. */
class Nm_offerings_catalog
{
    private $databasePath;

    public function __construct($databasePath = null, $projectRoot = null)
    {
        $projectRoot = $projectRoot ?: realpath(FCPATH . '..');
        $autoload = is_string($projectRoot) ? $projectRoot . '/internal/dependencies/autoload.php' : '';
        if ($autoload === '' || !is_file($autoload)) {
            throw new RuntimeException('The Numerimondes application autoloader is not available beside the Perfex installation.');
        }
        require_once $autoload;
        $this->databasePath = $databasePath ?: $projectRoot . '/storage/databases/offerings.sqlite';
        if (!is_file($this->databasePath) || !is_readable($this->databasePath) || !is_writable(dirname($this->databasePath))) {
            throw new RuntimeException('The offerings SQLite database is unavailable to the Perfex PHP process.');
        }
    }

    public function store()
    {
        return new OfferingStore($this->databasePath);
    }

    public function databasePath()
    {
        return $this->databasePath;
    }

    public function validate(array $catalogue)
    {
        if (!is_array($catalogue['offerings'] ?? null)) {
            throw new InvalidArgumentException('The catalogue must contain an offerings list.');
        }
        OfferingCatalogValidator::validate($catalogue['offerings']);
    }

    public function all($includeDetailOnly = false)
    {
        $revision = $this->store()->current('published');
        $items = $revision['catalogue']['offerings'] ?? [];
        $items = array_values(array_filter($items, static function ($item) use ($includeDetailOnly) {
            $visibility = $item['visibility'] ?? 'catalog';
            return $visibility !== 'hidden' && ($includeDetailOnly || $visibility === 'catalog');
        }));
        return $items;
    }

    public function find($id)
    {
        foreach ($this->all(true) as $offering) {
            if (($offering['id'] ?? null) === $id) return $offering;
        }
        return null;
    }

    public function saveDraft(array $catalogue, $staffId)
    {
        $this->validate($catalogue);
        return $this->store()->saveDraft($catalogue, 'perfex-staff:' . (int) $staffId);
    }

    public function publish($staffId)
    {
        $store = $this->store();
        $draft = $store->current('draft');
        if ($draft === null) throw new RuntimeException('Save a valid draft before publishing.');
        $this->validate($draft['catalogue']);
        return $store->publish('perfex-staff:' . (int) $staffId);
    }
}
