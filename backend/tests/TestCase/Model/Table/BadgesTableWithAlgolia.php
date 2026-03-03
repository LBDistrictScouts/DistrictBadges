<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\BadgesTable;
use App\Service\AlgoliaService;
use RuntimeException;

class BadgesTableWithAlgolia extends BadgesTable
{
    private ?AlgoliaService $algoliaService = null;

    /**
     * @param \App\Service\AlgoliaService $service Algolia service.
     * @return void
     */
    public function setAlgoliaService(AlgoliaService $service): void
    {
        $this->algoliaService = $service;
    }

    /**
     * @return \App\Service\AlgoliaService
     */
    protected function buildAlgoliaService(): AlgoliaService
    {
        if ($this->algoliaService === null) {
            throw new RuntimeException('Algolia service not set for test.');
        }

        return $this->algoliaService;
    }
}
