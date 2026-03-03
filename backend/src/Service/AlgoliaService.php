<?php

namespace App\Service;

use Algolia\AlgoliaSearch\Api\SearchClient;
use App\Model\Entity\Badge;
use Cake\Core\Configure;
use Cake\Datasource\EntityInterface;
use Cake\Log\Log;
use RuntimeException;
use Throwable;

class AlgoliaService
{
    private ?SearchClient $client;
    private bool $enabled;
    private string $appId;
    private string $apiKey;
    private string $indexName;

    public function __construct(?array $config = null, ?SearchClient $client = null)
    {
        $config = $config ?? (array)Configure::read('Algolia');

        $this->enabled = (bool)($config['enabled'] ?? false);
        $this->appId = (string)($config['appId'] ?? '');
        $this->apiKey = (string)($config['apiKey'] ?? '');
        $this->indexName = (string)($config['indexName'] ?? '');

        $this->client = $client ?? $this->initClient();
    }

    public function upsertBadge(EntityInterface $badge): void
    {
        if (!$this->enabled || $this->client === null) {
            return;
        }

        $objectId = (string)$badge->get('id');
        if ($objectId === '') {
            throw new RuntimeException('Algolia badge sync failed: badge id missing.');
        }

        if (!(bool)$badge->get('stocked')) {
            return;
        }

        $payload = $this->resolveBadgePayload($badge);

        $this->client->saveObject($this->indexName, $payload);
    }

    private function initClient(): ?SearchClient
    {
        if ($this->appId === '' || $this->apiKey === '' || $this->indexName === '') {
            Log::warning('Algolia badge sync disabled: missing ALGOLIA_APP_ID, ALGOLIA_ADMIN_API_KEY, or ALGOLIA_INDEX_BADGES.');
            return null;
        }

        try {
            return SearchClient::create($this->appId, $this->apiKey);
        } catch (Throwable $exception) {
            throw new RuntimeException(
                'Algolia badge sync failed: unable to initialize client.',
                0,
                $exception
            );
        }
    }

    private function resolveBadgePayload(EntityInterface $badge): array
    {
        if (!$badge instanceof Badge) {
            $badge = new Badge($badge->toArray());
        }

        return $badge->toAlgoliaPayload();
    }
}
