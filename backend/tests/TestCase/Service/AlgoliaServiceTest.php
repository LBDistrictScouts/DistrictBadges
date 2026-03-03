<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use Algolia\AlgoliaSearch\Api\SearchClient;
use App\Model\Entity\Badge;
use App\Service\AlgoliaService;
use Cake\TestSuite\TestCase;

/**
 * App\Service\AlgoliaService Test Case
 */
class AlgoliaServiceTest extends TestCase
{
    /**
     * Test upsertBadge method
     *
     * @return void
     * @link \App\Service\AlgoliaService::upsertBadge()
     */
    public function testUpsertBadge(): void
    {
        $client = $this->createMock(SearchClient::class);

        $client->expects($this->once())
            ->method('saveObject')
            ->with(
                'badges',
                $this->callback(function (array $payload): bool {
                    return ($payload['objectID'] ?? null) === 'badge-1'
                        && ($payload['badge_name'] ?? null) === 'Test Badge';
                })
            );

        $service = new AlgoliaService(
            [
                'enabled' => true,
                'appId' => 'app-id',
                'apiKey' => 'api-key',
                'indexName' => 'badges',
            ],
            $client
        );

        $badge = new Badge([
            'id' => 'badge-1',
            'badge_name' => 'Test Badge',
            'national_product_code' => 123,
            'stocked' => true,
            'on_hand_quantity' => 5,
            'receipted_quantity' => 2,
            'pending_quantity' => 1,
            'latest_hash' => 'hash',
            'price' => '9.99',
        ]);

        $service->upsertBadge($badge);
    }

    /**
     * Test upsertBadge short-circuits when disabled
     *
     * @return void
     */
    public function testUpsertBadgeWhenDisabled(): void
    {
        $client = $this->createMock(SearchClient::class);
        $client->expects($this->never())
            ->method('saveObject');

        $service = new AlgoliaService(
            [
                'enabled' => false,
                'appId' => 'app-id',
                'apiKey' => 'api-key',
                'indexName' => 'badges',
            ],
            $client
        );

        $badge = new Badge([
            'id' => 'badge-2',
            'badge_name' => 'Disabled Badge',
            'stocked' => true,
        ]);

        $service->upsertBadge($badge);
    }
}
