<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Entity\Badge;
use App\Model\Enum\BadgeStatus;
use App\Model\Enum\OrderStatus;
use App\Model\Enum\TagCategory;
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
        'app.BadgeTags',
        'app.BadgesBadgeTags',
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

    public function testBadgeTagsManyToManyAssociation(): void
    {
        $badge = $this->Badges->get(
            'f525eb6d-021c-4ef2-811f-feac8db8d35d',
            contain: ['BadgeTags'],
        );

        $this->assertCount(2, $badge->badge_tags);
        $this->assertSame('Beavers', $badge->badge_tags[0]->tag_name);
        $this->assertSame(TagCategory::Sections, $badge->badge_tags[0]->tag_category);
    }

    public function testCategorySpecificBadgeTagAssociations(): void
    {
        $badge = $this->Badges->get(
            'f525eb6d-021c-4ef2-811f-feac8db8d35d',
            contain: ['BadgeSections', 'BadgeTypes'],
        );

        $this->assertCount(1, $badge->badge_sections);
        $this->assertSame('Beavers', $badge->badge_sections[0]->tag_name);
        $this->assertCount(1, $badge->badge_types);
        $this->assertSame('Activity Badge', $badge->badge_types[0]->tag_name);
    }

    public function testAssociateTagsFromBadgeNameIsCaseInsensitiveAndIdempotent(): void
    {
        $badge = $this->Badges->get('0f3b8a4a-6c12-4f12-9a2e-0d9e4e4b2f70');
        $badge->set('badge_name', 'bEaVeRs Activity Badge');
        $this->Badges->saveOrFail($badge, ['skipAlgolia' => true]);

        $this->assertSame(2, $this->Badges->associateTagsFromBadgeName($badge));
        $this->assertSame(0, $this->Badges->associateTagsFromBadgeName($badge));

        $tagged = $this->Badges->get($badge->id, contain: ['BadgeTags']);
        $this->assertSame(
            ['Beavers', 'Activity Badge'],
            array_map(
                static fn($tag): string => $tag->tag_name,
                $tagged->badge_tags,
            ),
        );
    }

    public function testAssociateTagsFromBadgeNameSupportsStartAnchor(): void
    {
        $this->Badges->BadgeTags->updateAll(
            ['tag_search_text' => '^beavers'],
            ['id' => '3bb4858e-83d2-4ddd-8f72-bab835e05a2d'],
        );
        $badge = $this->Badges->get('0f3b8a4a-6c12-4f12-9a2e-0d9e4e4b2f70');

        $badge->set('badge_name', 'Young Beavers Award');
        $this->assertSame(0, $this->Badges->associateTagsFromBadgeName($badge, false));

        $badge->set('badge_name', 'bEaVeRs Award');
        $this->assertSame(1, $this->Badges->associateTagsFromBadgeName($badge, false));
    }

    public function testAssociateTagsFromBadgeNameSupportsEndAnchor(): void
    {
        $this->Badges->BadgeTags->updateAll(
            ['tag_search_text' => 'activity badge$'],
            ['id' => '1cc59bf0-37b3-42db-a5af-47745ac381d5'],
        );
        $badge = $this->Badges->get('0f3b8a4a-6c12-4f12-9a2e-0d9e4e4b2f70');

        $badge->set('badge_name', 'Activity Badge Staged');
        $this->assertSame(0, $this->Badges->associateTagsFromBadgeName($badge, false));

        $badge->set('badge_name', 'Explorers AcTiViTy BaDgE');
        $this->assertSame(1, $this->Badges->associateTagsFromBadgeName($badge, false));
    }

    public function testAssociateTagsFromBadgeNameSupportsBothAnchors(): void
    {
        $this->Badges->BadgeTags->updateAll(
            ['tag_search_text' => '^activity badge$'],
            ['id' => '1cc59bf0-37b3-42db-a5af-47745ac381d5'],
        );
        $badge = $this->Badges->get('0f3b8a4a-6c12-4f12-9a2e-0d9e4e4b2f70');

        $badge->set('badge_name', 'Young Activity Badge');
        $this->assertSame(0, $this->Badges->associateTagsFromBadgeName($badge, false));

        $badge->set('badge_name', 'AcTiViTy BaDgE');
        $this->assertSame(1, $this->Badges->associateTagsFromBadgeName($badge, false));
    }

    public function testAssociateTagsFromBadgeNameTreatsInternalDollarSignLiterally(): void
    {
        $this->Badges->BadgeTags->updateAll(
            ['tag_search_text' => 'Act$ivity'],
            ['id' => '1cc59bf0-37b3-42db-a5af-47745ac381d5'],
        );
        $badge = $this->Badges->get('0f3b8a4a-6c12-4f12-9a2e-0d9e4e4b2f70');

        $badge->set('badge_name', 'Activity Badge');
        $this->assertSame(0, $this->Badges->associateTagsFromBadgeName($badge, false));

        $badge->set('badge_name', 'Act$ivity Badge');
        $this->assertSame(1, $this->Badges->associateTagsFromBadgeName($badge, false));
    }

    public function testAssociateTagsFromBadgeNameTreatsRegexCharactersLiterally(): void
    {
        $this->Badges->BadgeTags->updateAll(
            ['tag_search_text' => '^Beavers.*'],
            ['id' => '3bb4858e-83d2-4ddd-8f72-bab835e05a2d'],
        );
        $badge = $this->Badges->get('0f3b8a4a-6c12-4f12-9a2e-0d9e4e4b2f70');

        $badge->set('badge_name', 'Beavers Award');
        $this->assertSame(0, $this->Badges->associateTagsFromBadgeName($badge, false));

        $badge->set('badge_name', 'Beavers.* Award');
        $this->assertSame(1, $this->Badges->associateTagsFromBadgeName($badge, false));
    }

    public function testAssociateTagsForAllBadges(): void
    {
        $this->Badges->updateAll(
            ['badge_name' => 'Beavers Activity Badge'],
            ['id' => '0f3b8a4a-6c12-4f12-9a2e-0d9e4e4b2f70'],
        );

        $result = $this->Badges->associateTagsForAllBadges();

        $this->assertSame(2, $result['badges']);
        $this->assertSame(2, $result['associations']);
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
            ->with($this->callback(
                fn(EntityInterface $badge): bool => count($badge->get('badge_sections')) === 0
                    && count($badge->get('badge_types')) === 0,
            ));

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

    public function testRefreshAlgoliaIndexExcludesUnstockedBadges(): void
    {
        $this->Badges->updateAll(
            ['status' => BadgeStatus::Unstocked->value],
            ['id' => '0f3b8a4a-6c12-4f12-9a2e-0d9e4e4b2f70'],
        );

        $service = $this->createMock(AlgoliaService::class);
        $service->expects($this->once())
            ->method('replaceBadges')
            ->with($this->callback(function (iterable $badges): bool {
                $badges = iterator_to_array($badges);

                return count($badges) === 1
                    && $badges[0]->id === 'f525eb6d-021c-4ef2-811f-feac8db8d35d'
                    && count($badges[0]->badge_sections) === 1
                    && count($badges[0]->badge_types) === 1;
            }))
            ->willReturn(1);

        $this->assertSame(1, $this->Badges->refreshAlgoliaIndex($service));
    }

    public function testUnlistedBadgeVirtualProperty(): void
    {
        $listed = new Badge(['national_product_code' => 123]);
        $unlisted = new Badge(['national_product_code' => null]);
        $unlistedWithImage = new Badge([
            'national_product_code' => null,
            'image_url' => 'https://example.com/unlisted-badge.jpg',
        ]);

        $this->assertFalse($listed->unlisted_badge);
        $this->assertTrue($unlisted->unlisted_badge);
        $this->assertTrue($unlisted->toArray()['unlisted_badge']);
        $this->assertSame(
            'https://example.com/unlisted-badge.jpg',
            $unlistedWithImage->image_medium_url,
        );
    }
}
