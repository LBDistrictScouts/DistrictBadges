<?php
declare(strict_types=1);

namespace App\Test\TestCase\Queue\Processor;

use App\Queue\Processor\BadgeImportProcessor;
use Cake\Core\Configure;
use Cake\Log\Engine\ArrayLog;
use Cake\Log\Log;
use Cake\TestSuite\TestCase;

class BadgeImportProcessorTest extends TestCase
{
    protected array $fixtures = [
        'app.Badges',
        'app.BadgeTags',
        'app.BadgesBadgeTags',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        Configure::write('Algolia.enabled', false);
        Log::setConfig('badge_import_test', [
            'className' => ArrayLog::class,
            'levels' => ['debug', 'notice', 'warning', 'error'],
            'scopes' => ['badge_import'],
        ]);
    }

    protected function tearDown(): void
    {
        Log::drop('badge_import_test');
        parent::tearDown();
    }

    public function testProcessCreatesBadgeFromObservedQueuePayload(): void
    {
        $processor = new BadgeImportProcessor();
        $processor->setTableLocator($this->getTableLocator());

        $result = $processor->process(json_encode($this->payload(), JSON_THROW_ON_ERROR));

        $this->assertSame(BadgeImportProcessor::ACK, $result);

        $badge = $this->getTableLocator()->get('Badges')
            ->find()
            ->where(['national_product_code' => 2477])
            ->firstOrFail();
        $this->assertSame('Beavers Flag Topper', $badge->badge_name);
        $this->assertSame(18.0, (float)$badge->price);
        $this->assertFalse($badge->stocked);
        $this->assertSame(0, $badge->on_hand_quantity);
        $this->assertSame('100779', $badge->national_data['result'][0]['SKUCode']);
        $this->assertSame('/product/1/0/100779.jpg', $badge->image_path);
        $this->assertSame(64, strlen($badge->national_product_hash));

        $badge = $this->getTableLocator()->get('Badges')->get(
            $badge->id,
            contain: ['BadgeSections', 'BadgeTypes'],
        );
        $this->assertSame(['Beavers'], array_column($badge->badge_sections, 'tag_name'));
        $this->assertSame([], $badge->badge_types);

        $messages = $this->badgeImportLogMessages();
        $this->assertCount(1, $messages);
        $this->assertStringContainsString('debug: Badge import succeeded: badge was created.', $messages[0]);
        $this->assertStringContainsString('"status":"success"', $messages[0]);
        $this->assertStringContainsString('"outcome":"created"', $messages[0]);
    }

    public function testProcessUpdatesExistingBadgeWithoutChangingStockControls(): void
    {
        $badges = $this->getTableLocator()->get('Badges');
        $existing = $badges->get('f525eb6d-021c-4ef2-811f-feac8db8d35d');
        $existing->set('national_product_code', 2477);
        $existing->set('on_hand_quantity', 12);
        $existing->set('reserve_quantity', 4);
        $badges->saveOrFail($existing, [
            'skipAlgolia' => true,
            'skipNationalData' => true,
        ]);

        $processor = new BadgeImportProcessor();
        $processor->setTableLocator($this->getTableLocator());
        $result = $processor->process(json_encode($this->payload(), JSON_THROW_ON_ERROR));

        $this->assertSame(BadgeImportProcessor::ACK, $result);
        $updated = $badges->get($existing->id);
        $this->assertSame('Beavers Flag Topper', $updated->badge_name);
        $this->assertSame(12, $updated->on_hand_quantity);
        $this->assertSame(4, $updated->reserve_quantity);
        $this->assertSame((string)$existing->latest_hash, (string)$updated->latest_hash);
    }

    public function testProcessIgnoresBadgeNameEndingWithSpaceHyphen(): void
    {
        $badges = $this->getTableLocator()->get('Badges');
        $before = $badges->find()->count();
        $payload = $this->payload();
        $payload['BadgeName'] = 'Beavers Flag Topper -';

        $processor = new BadgeImportProcessor();
        $processor->setTableLocator($this->getTableLocator());
        $result = $processor->process(json_encode($payload, JSON_THROW_ON_ERROR));

        $this->assertSame(BadgeImportProcessor::ACK, $result);
        $this->assertSame($before, $badges->find()->count());
        $this->assertFalse($badges->exists(['national_product_code' => 2477]));
        $messages = $this->badgeImportLogMessages();
        $this->assertCount(1, $messages);
        $this->assertStringContainsString('Badge import skipped.', $messages[0]);
        $this->assertStringContainsString('"status":"skipped"', $messages[0]);
        $this->assertStringContainsString('Badge name ends with \\" -\\".', $messages[0]);
    }

    public function testProcessDoesNotIgnoreHyphenWithinBadgeName(): void
    {
        $badges = $this->getTableLocator()->get('Badges');
        $payload = $this->payload();
        $payload['BadgeName'] = 'Beavers - Flag Topper';

        $processor = new BadgeImportProcessor();
        $processor->setTableLocator($this->getTableLocator());
        $result = $processor->process(json_encode($payload, JSON_THROW_ON_ERROR));

        $this->assertSame(BadgeImportProcessor::ACK, $result);
        $this->assertTrue($badges->exists(['national_product_code' => 2477]));
    }

    public function testProcessRejectsInvalidJson(): void
    {
        $processor = new BadgeImportProcessor();

        $this->assertSame(BadgeImportProcessor::REJECT, $processor->process('{'));
    }

    public function testProcessRejectsPayloadThatDoesNotMatchSchema(): void
    {
        $payload = $this->payload();
        unset($payload['NationalBadgeID']);
        $processor = new BadgeImportProcessor();

        $result = $processor->process(json_encode($payload, JSON_THROW_ON_ERROR));

        $this->assertSame(BadgeImportProcessor::REJECT, $result);
        $messages = $this->badgeImportLogMessages();
        $this->assertCount(1, $messages);
        $this->assertStringContainsString('failed schema validation', $messages[0]);
        $this->assertStringContainsString('"status":"failure"', $messages[0]);
        $this->assertStringContainsString('NationalBadgeID', $messages[0]);
    }

    /**
     * @return array<string>
     */
    private function badgeImportLogMessages(): array
    {
        $logger = Log::engine('badge_import_test');
        $this->assertInstanceOf(ArrayLog::class, $logger);

        return $logger->read();
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        return [
            'BadgeName' => 'Beavers Flag Topper',
            'NationalBadgeID' => 2477,
            'Price' => 18,
            'ImageURL' => '/product/1/0/100779.jpg',
            'ProductURL' => '/scouts-leaders-and-volunteers/beaver-scouts-flag-topper',
            'CatagoryPath' => 'Scouts Leaders and Volunteers',
            'CategoryIDs' => '"2","824","1580"',
            'Weight' => 0.01,
            'BreadCrumb' => 'Scouts Leaders and Volunteers',
            'Description' => 'Wooden Beaver Scout topper.',
            'CanonicalURL' => '/scouts-leaders-and-volunteers/beaver-scouts-flag-topper',
            'OGImage' => '/static/media/catalog/product/1/0/100779.jpg',
            'Processed' => null,
            'SKUCode' => '100779',
            'id' => 1039,
            'createdAt' => '2026-03-03T23:31:49.283Z',
            'updatedAt' => '2026-06-07T00:01:07.756Z',
        ];
    }
}
