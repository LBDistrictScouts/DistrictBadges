<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Enum\BadgeStatus;
use App\Model\Enum\OrderStatus;
use App\Service\AlgoliaService;
use Cake\Datasource\EntityInterface;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\BadgesTable Test Case
 */
class BadgesTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\BadgesTable
     */
    protected $Badges;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Groups',
        'app.Accounts',
        'app.Users',
        'app.Badges',
        'app.Orders',
        'app.OrderLines',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $locator = $this->getTableLocator();
        $locator->clear();
        $this->Badges = $locator->get('Badges', ['className' => BadgesTableWithAlgolia::class]);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->Badges);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @link \App\Model\Table\BadgesTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $entity = $this->Badges->newEntity([
            'badge_name' => '',
            'stocked' => 'not-bool',
            'reserve_quantity' => -1,
            'replenishment_price' => 'not-a-decimal',
        ]);

        $errors = $entity->getErrors();
        $this->assertArrayHasKey('badge_name', $errors);
        $this->assertArrayHasKey('stocked', $errors);
        $this->assertArrayHasKey('reserve_quantity', $errors);
        $this->assertArrayHasKey('replenishment_price', $errors);

        $valid = $this->Badges->newEntity([
            'badge_name' => 'Test Badge',
            'stocked' => true,
            'national_product_code' => null,
            'national_data' => null,
            'reserve_quantity' => 3,
            'replenishment_price' => '1.25',
        ]);
        $this->assertSame([], $valid->getErrors());
    }

    /**
     * Test save method
     *
     * @return void
     */
    public function testSave(): void
    {
        $entity = $this->Badges->newEntity([
            'badge_name' => 'New Badge',
            'stocked' => true,
            'national_product_code' => null,
            'national_data' => null,
            'reserve_quantity' => 4,
            'replenishment_price' => '2.50',
        ]);

        $result = $this->Badges->save($entity, ['skipAlgolia' => true]);
        $this->assertNotFalse($result);
        $this->assertNotEmpty($result->id);

        $saved = $this->Badges->get($result->id);
        $this->assertSame('New Badge', $saved->badge_name);
        $this->assertSame(BadgeStatus::Unavailable, $saved->status);
        $this->assertTrue((bool)$saved->stocked);
        $this->assertNull($saved->national_product_code);
        $this->assertNull($saved->national_data);
        $this->assertSame(4, $saved->reserve_quantity);
        $this->assertSame(2.5, (float)$saved->replenishment_price);
    }

    public function testStatusIsDerivedFromStockQuantities(): void
    {
        $badge = $this->Badges->get('0f3b8a4a-6c12-4f12-9a2e-0d9e4e4b2f70');

        $badge->set('on_hand_quantity', 0);
        $badge->set('pending_quantity', 5);
        $this->Badges->saveOrFail($badge, ['skipAlgolia' => true]);
        $this->assertSame(BadgeStatus::OnBackOrder, $badge->status);

        $badge->set('stocked', false);
        $this->Badges->saveOrFail($badge, ['skipAlgolia' => true]);
        $this->assertSame(BadgeStatus::Deprecated, $badge->status);

        $badge->set('on_hand_quantity', 2);
        $this->Badges->saveOrFail($badge, ['skipAlgolia' => true]);
        $this->assertSame(BadgeStatus::Deprecated, $badge->status);

        $badge->set('stocked', true);
        $badge->set('on_hand_quantity', 2);
        $this->Badges->saveOrFail($badge, ['skipAlgolia' => true]);
        $this->assertSame(BadgeStatus::Available, $badge->status);

        $badge->set('on_hand_quantity', 0);
        $badge->set('pending_quantity', 0);
        $this->Badges->saveOrFail($badge, ['skipAlgolia' => true]);
        $this->assertSame(BadgeStatus::Unavailable, $badge->status);

        $badge->set('stocked', false);
        $this->Badges->saveOrFail($badge, ['skipAlgolia' => true]);
        $this->assertSame(BadgeStatus::Unstocked, $badge->status);
    }

    public function testGetReplenishmentRequirements(): void
    {
        $badgeId = 'f525eb6d-021c-4ef2-811f-feac8db8d35d';
        $this->Badges->updateAll([
            'on_hand_quantity' => 2,
            'pending_quantity' => 3,
            'reserve_quantity' => 4,
        ], ['id' => $badgeId]);
        $this->Badges->OrderLines->Orders->updateAll(
            ['status' => OrderStatus::Placed->value],
            ['id' => 'dd7b14cc-abe6-4e58-b63d-070678d78644'],
        );
        $this->Badges->OrderLines->updateAll([
            'quantity' => 10,
            'fulfilled_quantity' => 2,
        ], ['id' => 'be20de8c-eea8-4114-a98e-1d55e483e8db']);

        $requirements = $this->Badges->getReplenishmentRequirements();

        $this->assertSame(7, $requirements[$badgeId]['quantity']);
    }

    public function testGetReplenishmentRequirementsExcludesIneligibleBadges(): void
    {
        $badgeId = 'f525eb6d-021c-4ef2-811f-feac8db8d35d';
        $this->Badges->OrderLines->Orders->updateAll(
            ['status' => OrderStatus::Fulfilled->value],
            ['id' => 'dd7b14cc-abe6-4e58-b63d-070678d78644'],
        );
        $this->assertArrayNotHasKey($badgeId, $this->Badges->getReplenishmentRequirements());

        $this->Badges->OrderLines->Orders->updateAll(
            ['status' => OrderStatus::Placed->value],
            ['id' => 'dd7b14cc-abe6-4e58-b63d-070678d78644'],
        );
        $this->Badges->updateAll(['stocked' => false], ['id' => $badgeId]);
        $this->assertArrayNotHasKey($badgeId, $this->Badges->getReplenishmentRequirements());
    }

    /**
     * Test afterSave triggers Algolia sync.
     *
     * @return void
     */
    public function testAfterSaveTriggersAlgoliaSync(): void
    {
        $service = $this->createMock(AlgoliaService::class);
        $service->expects($this->once())
            ->method('upsertBadge')
            ->with($this->isInstanceOf(EntityInterface::class));

        $this->Badges->setAlgoliaService($service);

        $entity = $this->Badges->newEntity([
            'badge_name' => 'Algolia Badge',
            'stocked' => true,
        ]);

        $result = $this->Badges->save($entity);
        $this->assertNotFalse($result);
    }

    public function testTransitionFromUnavailableToUnstockedDeletesFromAlgolia(): void
    {
        $service = $this->createMock(AlgoliaService::class);
        $service->expects($this->once())
            ->method('deleteBadge')
            ->with($this->callback(
                fn(EntityInterface $badge): bool => $badge->get('status') === BadgeStatus::Unstocked,
            ));
        $service->expects($this->never())
            ->method('upsertBadge');

        $this->Badges->setAlgoliaService($service);

        $badge = $this->Badges->get('0f3b8a4a-6c12-4f12-9a2e-0d9e4e4b2f70');
        $this->assertSame(BadgeStatus::Unavailable, $badge->status);

        $badge->set('stocked', false);
        $this->Badges->saveOrFail($badge);
    }

    public function testDeprecatedTransitionToUnstockedDeletesFromAlgolia(): void
    {
        $badge = $this->Badges->get('0f3b8a4a-6c12-4f12-9a2e-0d9e4e4b2f70');
        $badge->set('stocked', false);
        $badge->set('pending_quantity', 5);
        $this->Badges->saveOrFail($badge, ['skipAlgolia' => true]);
        $this->assertSame(BadgeStatus::Deprecated, $badge->status);

        $service = $this->createMock(AlgoliaService::class);
        $service->expects($this->once())
            ->method('deleteBadge')
            ->with($this->callback(
                fn(EntityInterface $savedBadge): bool => $savedBadge->get('status')
                    === BadgeStatus::Unstocked,
            ));
        $service->expects($this->never())
            ->method('upsertBadge');
        $this->Badges->setAlgoliaService($service);

        $badge->set('pending_quantity', 0);
        $this->Badges->saveOrFail($badge);
    }

    public function testNewUnstockedBadgeDoesNotSyncToAlgolia(): void
    {
        $service = $this->createMock(AlgoliaService::class);
        $service->expects($this->never())
            ->method('upsertBadge');
        $service->expects($this->never())
            ->method('deleteBadge');
        $this->Badges->setAlgoliaService($service);

        $badge = $this->Badges->newEntity([
            'badge_name' => 'Never Stocked',
            'stocked' => false,
        ]);

        $this->Badges->saveOrFail($badge);
        $this->assertSame(BadgeStatus::Unstocked, $badge->status);
    }
}
