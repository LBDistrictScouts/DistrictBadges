<?php
declare(strict_types=1);

namespace App\Service;

use Algolia\AlgoliaSearch\Api\SearchClient;
use App\Model\Entity\Badge;
use App\Model\Enum\BadgeStatus;
use App\Utility\Value;
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

    /**
     * @param array<string, mixed>|null $config Config override.
     * @param \Algolia\AlgoliaSearch\Api\SearchClient|null $client Client override.
     */
    public function __construct(?array $config = null, ?SearchClient $client = null)
    {
        $config = $config ?? (array)Configure::read('Algolia');

        $this->enabled = (bool)($config['enabled'] ?? false);
        $this->appId = Value::string($config['appId'] ?? '');
        $this->apiKey = Value::string($config['apiKey'] ?? '');
        $this->indexName = Value::string($config['indexName'] ?? '');

        $clientOverride = $client ?? ($config['client'] ?? null);

        if ($clientOverride instanceof SearchClient) {
            $this->client = $clientOverride;
        } elseif ($this->enabled) {
            $this->client = $this->initClient();
        } else {
            $this->client = null;
        }
    }

    /**
     * @param \Cake\Datasource\EntityInterface $badge Badge entity.
     * @return void
     */
    public function upsertBadge(EntityInterface $badge): void
    {
        if (!$this->enabled || $this->client === null) {
            return;
        }

        $this->requireObjectId($badge);

        $payload = $this->resolveBadgePayload($badge);

        $this->client->saveObject($this->indexName, $payload);
    }

    /**
     * Replace the complete badge index with the supplied searchable badges.
     *
     * @param iterable<\Cake\Datasource\EntityInterface> $badges Badges to index.
     * @return int Number of indexed badges.
     */
    public function replaceBadges(iterable $badges): int
    {
        if (!$this->enabled || $this->client === null) {
            return 0;
        }

        $payloads = [];
        foreach ($badges as $badge) {
            $status = $badge->get('status');
            if (
                $status === BadgeStatus::Unstocked
                || $status === BadgeStatus::Unstocked->value
            ) {
                continue;
            }

            $this->requireObjectId($badge);
            $payloads[] = $this->resolveBadgePayload($badge);
        }

        $this->client->replaceAllObjects($this->indexName, $payloads);

        return count($payloads);
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled && $this->client !== null;
    }

    /**
     * Remove a badge that should no longer be searchable.
     *
     * @param \Cake\Datasource\EntityInterface $badge Badge entity.
     * @return void
     */
    public function deleteBadge(EntityInterface $badge): void
    {
        if (!$this->enabled || $this->client === null) {
            return;
        }

        $this->client->deleteObject($this->indexName, $this->requireObjectId($badge));
    }

    /**
     * @return \Algolia\AlgoliaSearch\Api\SearchClient|null
     */
    private function initClient(): ?SearchClient
    {
        if ($this->appId === '' || $this->apiKey === '' || $this->indexName === '') {
            Log::warning(
                'Algolia badge sync disabled: missing ALGOLIA_APP_ID, ALGOLIA_ADMIN_API_KEY, '
                . 'or ALGOLIA_INDEX_BADGES.',
            );

            return null;
        }

        try {
            $client = SearchClient::create($this->appId, $this->apiKey);
            if (!$client instanceof SearchClient) {
                throw new RuntimeException('Algolia did not return a search client.');
            }

            return $client;
        } catch (Throwable $exception) {
            throw new RuntimeException(
                'Algolia badge sync failed: unable to initialize client.',
                0,
                $exception,
            );
        }
    }

    /**
     * @param \Cake\Datasource\EntityInterface $badge Badge entity.
     * @return array<string, mixed>
     */
    private function resolveBadgePayload(EntityInterface $badge): array
    {
        if (!$badge instanceof Badge) {
            $badge = new Badge($badge->toArray());
        }

        return $badge->toAlgoliaPayload();
    }

    /**
     * @param \Cake\Datasource\EntityInterface $badge Badge entity.
     * @return string
     */
    private function requireObjectId(EntityInterface $badge): string
    {
        $objectId = Value::string($badge->get('id'));
        if ($objectId === '') {
            throw new RuntimeException('Algolia badge sync failed: badge id missing.');
        }

        return $objectId;
    }
}
