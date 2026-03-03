<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use Algolia\AlgoliaSearch\Api\SearchClient;
use App\Model\Entity\Badge;
use App\Service\AlgoliaService;
use Cake\Log\Engine\ArrayLog;
use Cake\Log\Log;
use Cake\ORM\Entity;
use Cake\TestSuite\TestCase;
use RuntimeException;

/**
 * App\Service\AlgoliaService Test Case
 */
class AlgoliaServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();
        Log::reset();
    }

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
                }),
            );

        $service = new AlgoliaService(
            [
                'enabled' => true,
                'appId' => 'app-id',
                'apiKey' => 'api-key',
                'indexName' => 'badges',
            ],
            $client,
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
            $client,
        );

        $badge = new Badge([
            'id' => 'badge-2',
            'badge_name' => 'Disabled Badge',
            'stocked' => true,
        ]);

        $service->upsertBadge($badge);
    }

    public function testUpsertBadgeSkipsWhenNotStocked(): void
    {
        $client = $this->createMock(SearchClient::class);
        $client->expects($this->never())
            ->method('saveObject');

        $service = new AlgoliaService(
            [
                'enabled' => true,
                'appId' => 'app-id',
                'apiKey' => 'api-key',
                'indexName' => 'badges',
            ],
            $client,
        );

        $badge = new Badge([
            'id' => 'badge-3',
            'badge_name' => 'Not Stocked',
            'stocked' => false,
        ]);

        $service->upsertBadge($badge);
    }

    public function testUpsertBadgeThrowsWhenMissingId(): void
    {
        $client = $this->createMock(SearchClient::class);
        $client->expects($this->never())
            ->method('saveObject');

        $service = new AlgoliaService(
            [
                'enabled' => true,
                'appId' => 'app-id',
                'apiKey' => 'api-key',
                'indexName' => 'badges',
            ],
            $client,
        );

        $badge = new Badge([
            'badge_name' => 'Missing ID',
            'stocked' => true,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Algolia badge sync failed: badge id missing.');

        $service->upsertBadge($badge);
    }

    public function testUpsertBadgeBuildsPayloadFromGenericEntity(): void
    {
        $client = $this->createMock(SearchClient::class);
        $client->expects($this->once())
            ->method('saveObject')
            ->with(
                'badges',
                $this->callback(function (array $payload): bool {
                    return ($payload['objectID'] ?? null) === 'badge-4'
                        && ($payload['badge_name'] ?? null) === 'Generic Entity';
                }),
            );

        $service = new AlgoliaService(
            [
                'enabled' => true,
                'appId' => 'app-id',
                'apiKey' => 'api-key',
                'indexName' => 'badges',
            ],
            $client,
        );

        $entity = new Entity([
            'id' => 'badge-4',
            'badge_name' => 'Generic Entity',
            'stocked' => true,
            'price' => '5.00',
        ]);

        $service->upsertBadge($entity);
    }

    public function testInitClientLogsWarningWhenMissingConfig(): void
    {
        Log::setConfig('test', [
            'className' => ArrayLog::class,
            'levels' => ['warning'],
        ]);

        $service = new AlgoliaService([
            'enabled' => true,
            'appId' => '',
            'apiKey' => '',
            'indexName' => '',
        ]);

        $badge = new Badge([
            'id' => 'badge-5',
            'badge_name' => 'No Config',
            'stocked' => true,
        ]);

        $service->upsertBadge($badge);

        $logger = Log::engine('test');
        $this->assertInstanceOf(ArrayLog::class, $logger);
        $messages = $logger->read();
        $this->assertNotEmpty($messages);
        $this->assertStringContainsString('Algolia badge sync disabled', $messages[0]);
    }
}
