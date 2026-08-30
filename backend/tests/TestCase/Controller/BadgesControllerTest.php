<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Model\Enum\BadgeStatus;
use App\Model\Enum\TagCategory;
use App\Model\Enum\TransactionType;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\BadgesController Test Case
 *
 * @link \App\Controller\BadgesController
 */
class BadgesControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Groups',
        'app.Accounts',
        'app.Users',
        'app.Audits',
        'app.Badges',
        'app.BadgeTags',
        'app.BadgesBadgeTags',
        'app.Fulfilments',
        'app.Replenishments',
        'app.Orders',
        'app.OrderLines',
        'app.StockTransactions',
    ];

    /**
     * Test index method
     *
     * @return void
     * @link \App\Controller\BadgesController::index()
     */
    public function testIndex(): void
    {
        $this->get('/badges');
        $this->assertResponseOk();
        $this->assertResponseContains('Lorem ipsum dolor sit amet');
        $this->assertResponseContains('Available');
        $this->assertResponseNotContains('National Product Code');
        $this->assertResponseNotContains('Replenishment Price');
        $this->assertResponseNotContains('Reserve');
        $this->assertResponseContains('All availability statuses');
        $this->assertResponseContains('Availability Status');
        $this->assertResponseContains('Stocking');
        $this->assertResponseContains('All badges');
        $this->assertResponseContains('Listing');
        $this->assertResponseContains('All listings');
        $this->assertResponseContains('All sections');
        $this->assertResponseContains('All badge types');
        $this->assertResponseContains(
            '/badges/stock-transactions/f525eb6d-021c-4ef2-811f-feac8db8d35d',
        );
        $this->assertResponseNotContains(
            '/badges/delete/f525eb6d-021c-4ef2-811f-feac8db8d35d',
        );
        $this->assertResponseContains(
            '/badges/delete/0f3b8a4a-6c12-4f12-9a2e-0d9e4e4b2f70',
        );
    }

    public function testIndexFilters(): void
    {
        $this->get('/badges?name=lOrEm&status=' . BadgeStatus::Available->value);
        $this->assertResponseOk();
        $this->assertResponseContains('Lorem ipsum dolor sit amet');
        $this->assertResponseNotContains('Second badge');

        $this->get('/badges?name=Missing');
        $this->assertResponseOk();
        $this->assertResponseNotContains('Lorem ipsum dolor sit amet');

        $this->get('/badges?status=' . BadgeStatus::Unavailable->value);
        $this->assertResponseOk();
        $this->assertResponseNotContains('Lorem ipsum dolor sit amet');
        $this->assertResponseContains('Second badge');
    }

    public function testCardsUsesCatalogueLayoutWithIndexFiltersAndActions(): void
    {
        $this->get('/badges/cards?name=Lorem');

        $this->assertResponseOk();
        $this->assertResponseContains('badge-card-grid');
        $this->assertResponseContains('data-badge-index-controls');
        $this->assertResponseContains('badge-product-card');
        $this->assertResponseContains('Lorem ipsum dolor sit amet');
        $this->assertResponseNotContains('Second badge');
        $this->assertResponseContains('All availability statuses');
        $this->assertResponseContains('All sections');
        $this->assertResponseContains('/badges/view/f525eb6d-021c-4ef2-811f-feac8db8d35d');
        $this->assertResponseContains('/badges/edit/f525eb6d-021c-4ef2-811f-feac8db8d35d');
        $this->assertResponseContains(
            '/badges/stock-transactions/f525eb6d-021c-4ef2-811f-feac8db8d35d',
        );
    }

    public function testStockUsesCountGridWithoutImages(): void
    {
        $this->get('/badges/stock?name=Lorem');

        $this->assertResponseOk();
        $this->assertResponseContains('badge-stock-grid');
        $this->assertResponseContains('Filters & Sorting');
        $this->assertResponseContains('Lorem ipsum dolor sit amet');
        $this->assertResponseNotContains('Second badge');
        $this->assertResponseNotContains('<img');
        $this->assertResponseContains('badge-tag--section');
        $this->assertResponseContains('badge-tag--type');
        foreach (['On Hand', 'Pending', 'Reserve', 'Receipted', 'Fulfilled', 'Invoiced'] as $label) {
            $this->assertResponseContains($label);
        }
        $this->assertResponseContains('/badges/view/f525eb6d-021c-4ef2-811f-feac8db8d35d');
        $this->assertResponseContains(
            '/badges/stock-transactions/f525eb6d-021c-4ef2-811f-feac8db8d35d',
        );
    }

    public function testStockCanSortByStockAmounts(): void
    {
        $badges = $this->getTableLocator()->get('Badges');
        $badges->updateAll(['on_hand_quantity' => 2], [
            'id' => 'f525eb6d-021c-4ef2-811f-feac8db8d35d',
        ]);
        $badges->updateAll(['on_hand_quantity' => 20, 'stocked' => true], [
            'id' => '0f3b8a4a-6c12-4f12-9a2e-0d9e4e4b2f70',
        ]);

        $this->get('/badges/stock?sort=on_hand_quantity&direction=desc');

        $this->assertResponseOk();
        $body = (string)$this->_response->getBody();
        $this->assertLessThan(
            strpos($body, 'Lorem ipsum dolor sit amet'),
            strpos($body, 'Second badge'),
        );
        foreach (
            [
                'on_hand_quantity',
                'pending_quantity',
                'reserve_quantity',
                'receipted_quantity',
                'fulfilled_quantity',
                'invoiced_quantity',
            ] as $field
        ) {
            $this->assertResponseContains(sprintf('value="%s"', $field));
        }
        $this->assertResponseContains('Ascending');
        $this->assertResponseContains('Descending');
    }

    public function testBadgeViewStyleIsRetainedForTheSession(): void
    {
        $this->get('/badges/stock');
        $this->assertResponseOk();
        $this->assertResponseContains('badge-stock-grid');

        $this->_session = ['Badges' => ['viewStyle' => 'stock']];
        $this->get('/badges');
        $this->assertResponseOk();
        $this->assertResponseContains('badge-stock-grid');

        $this->get('/badges/table');
        $this->assertResponseOk();
        $this->assertResponseNotContains('badge-stock-grid');
        $this->assertResponseContains('<table>');

        $this->_session = ['Badges' => ['viewStyle' => 'table']];
        $this->get('/badges');
        $this->assertResponseOk();
        $this->assertResponseNotContains('badge-stock-grid');
        $this->assertResponseContains('<table>');
    }

    public function testIndexPaginationPreservesFilters(): void
    {
        $badges = $this->getTableLocator()->get('Badges');
        $badges->updateAll(['stocked' => false], []);
        for ($index = 0; $index < 9; $index++) {
            $badges->saveOrFail($badges->newEntity([
                'badge_name' => "Pagination badge {$index}",
                'national_data' => '{}',
                'stocked' => false,
                'status' => BadgeStatus::Unavailable->value,
            ]), ['skipAlgolia' => true]);
        }

        $this->get('/badges?stocked=0&limit=10');

        $this->assertResponseOk();
        $this->assertResponseContains('Page 1 of 2');
        $this->assertResponseRegExp(
            '/href="(?=[^"]*page=2)(?=[^"]*stocked=0)[^"]+"[^>]*>2<\/a>/',
        );
    }

    public function testIndexPaginationPreservesEmptyAllFilter(): void
    {
        $badges = $this->getTableLocator()->get('Badges');
        for ($index = 0; $index < 9; $index++) {
            $badges->saveOrFail($badges->newEntity([
                'badge_name' => "Pagination badge {$index}",
                'national_data' => '{}',
                'stocked' => true,
                'status' => BadgeStatus::Available->value,
            ]), ['skipAlgolia' => true]);
        }

        $this->get('/badges?stocked=&limit=10');

        $this->assertResponseOk();
        $this->assertResponseContains('Page 1 of 2');
        $this->assertResponseRegExp(
            '/href="(?=[^"]*page=2)(?=[^"]*stocked=)[^"]+"[^>]*>2<\/a>/',
        );
    }

    public function testIndexPaginationLimitIsCachedInSession(): void
    {
        $this->get('/badges?limit=75');

        $this->assertResponseOk();
        $this->assertSession(75, 'Pagination.limit');
        $this->assertResponseRegExp('/<option value="75"[^>]*selected[^>]*>75<\/option>/');

        $this->session(['Pagination' => ['limit' => 75]]);
        $this->get('/badges');

        $this->assertResponseOk();
        $this->assertResponseRegExp('/<option value="75"[^>]*selected[^>]*>75<\/option>/');
    }

    public function testIndexFiltersStockedSeparatelyFromAvailability(): void
    {
        $badges = $this->getTableLocator()->get('Badges');
        $unstocked = $badges->get('0f3b8a4a-6c12-4f12-9a2e-0d9e4e4b2f70');
        $unstocked->set('stocked', false);
        $badges->saveOrFail($unstocked, ['skipAlgolia' => true]);

        $this->get('/badges?stocked=1');
        $this->assertResponseOk();
        $this->assertResponseContains('Lorem ipsum dolor sit amet');
        $this->assertResponseNotContains('Second badge');

        $this->get('/badges?stocked=0');
        $this->assertResponseOk();
        $this->assertResponseNotContains('Lorem ipsum dolor sit amet');
        $this->assertResponseContains('Second badge');

        $this->get('/badges?stocked=0&status=' . BadgeStatus::Available->value);
        $this->assertResponseOk();
        $this->assertResponseNotContains('Lorem ipsum dolor sit amet');
        $this->assertResponseNotContains('Second badge');
    }

    public function testIndexFiltersListedAndUnlistedBadges(): void
    {
        $this->get('/badges?stocked=&listed=1');
        $this->assertResponseOk();
        $this->assertResponseContains('Lorem ipsum dolor sit amet');
        $this->assertResponseNotContains('Second badge');

        $this->get('/badges?stocked=&listed=0');
        $this->assertResponseOk();
        $this->assertResponseNotContains('Lorem ipsum dolor sit amet');
        $this->assertResponseContains('Second badge');
    }

    public function testIndexDefaultsToStockedBadges(): void
    {
        $badges = $this->getTableLocator()->get('Badges');
        $unstocked = $badges->get('0f3b8a4a-6c12-4f12-9a2e-0d9e4e4b2f70');
        $unstocked->set('stocked', false);
        $badges->saveOrFail($unstocked, ['skipAlgolia' => true]);

        $this->get('/badges');
        $this->assertResponseOk();
        $this->assertResponseContains('Lorem ipsum dolor sit amet');
        $this->assertResponseNotContains('Second badge');

        $this->get('/badges?stocked=');
        $this->assertResponseOk();
        $this->assertResponseContains('Lorem ipsum dolor sit amet');
        $this->assertResponseContains('Second badge');
    }

    public function testIndexAvailabilityFilterExcludesStockingStatuses(): void
    {
        $this->get('/badges?stocked=');

        $this->assertResponseOk();
        $this->assertResponseNotContains('<option value="30">Deprecated</option>');
        $this->assertResponseNotContains('<option value="40">Unstocked</option>');
    }

    public function testIndexFiltersBySectionAndBadgeType(): void
    {
        $sectionId = '3bb4858e-83d2-4ddd-8f72-bab835e05a2d';
        $typeId = '1cc59bf0-37b3-42db-a5af-47745ac381d5';

        $this->get("/badges?section_tag={$sectionId}");
        $this->assertResponseOk();
        $this->assertResponseContains('Lorem ipsum dolor sit amet');
        $this->assertResponseNotContains('Second badge');

        $this->get("/badges?type_tag={$typeId}");
        $this->assertResponseOk();
        $this->assertResponseContains('Lorem ipsum dolor sit amet');
        $this->assertResponseNotContains('Second badge');

        $this->get("/badges?section_tag={$sectionId}&type_tag={$typeId}");
        $this->assertResponseOk();
        $this->assertResponseContains('Lorem ipsum dolor sit amet');
        $this->assertResponseNotContains('Second badge');
    }

    public function testIndexShowsStockActionOnlyForUnstockedBadges(): void
    {
        $badges = $this->getTableLocator()->get('Badges');
        $unstocked = $badges->get('0f3b8a4a-6c12-4f12-9a2e-0d9e4e4b2f70');
        $unstocked->set('stocked', false);
        $badges->saveOrFail($unstocked, ['skipAlgolia' => true]);

        $this->get('/badges?stocked=0');

        $this->assertResponseOk();
        $this->assertResponseContains('/badges/activate/' . $unstocked->id);
        $this->assertResponseRegExp('/<a[^>]*>Stock<\/a>/');
    }

    public function testIndexStockActionPreservesFilters(): void
    {
        $badges = $this->getTableLocator()->get('Badges');
        $unstocked = $badges->get('0f3b8a4a-6c12-4f12-9a2e-0d9e4e4b2f70');
        $unstocked->set('stocked', false);
        $badges->saveOrFail($unstocked, ['skipAlgolia' => true]);

        $this->get('/badges?name=Second&stocked=0');

        $this->assertResponseOk();
        $this->assertResponseContains('/badges/activate/' . $unstocked->id . '?name=Second');
        $this->assertResponseContains('stocked=0');
    }

    /**
     * Test view method
     *
     * @return void
     * @link \App\Controller\BadgesController::view()
     */
    public function testView(): void
    {
        $id = 'f525eb6d-021c-4ef2-811f-feac8db8d35d';
        $badges = $this->getTableLocator()->get('Badges');
        $badges->updateAll(
            [
                'on_hand_quantity' => 11,
                'reserve_quantity' => 10,
                'pending_quantity' => 12,
                'receipted_quantity' => 13,
                'fulfilled_quantity' => 14,
                'invoiced_quantity' => 15,
            ],
            ['id' => $id],
        );

        $this->get("/badges/view/{$id}");
        $this->assertResponseOk();
        $this->assertResponseContains('badge-product-card');
        $this->assertResponseContains('Lorem ipsum dolor sit amet');
        $this->assertResponseContains('Available');
        $this->assertResponseContains('Stock Amounts');
        $this->assertResponseContains('Calculated Stock');
        $this->assertResponseContains('Historic Stock Movements');
        $this->assertResponseContains('Sections');
        $this->assertResponseContains('Beavers');
        $this->assertResponseContains('Badge Types');
        $this->assertResponseContains('Activity Badge');
        $this->assertResponseContains('badge-tag--section');
        $this->assertResponseContains('badge-tag--type');
        $this->assertResponseRegExp('/data-stock-amount="on-hand">\s*11\s*<\/strong>/');
        $this->assertResponseRegExp('/data-stock-amount="reserve">\s*10\s*<\/strong>/');
        $this->assertResponseRegExp('/data-stock-amount="pending">\s*12\s*<\/strong>/');
        $this->assertResponseRegExp('/data-stock-amount="receipted">\s*13\s*<\/strong>/');
        $this->assertResponseRegExp('/data-stock-amount="fulfilled">\s*14\s*<\/strong>/');
        $this->assertResponseRegExp('/data-stock-amount="invoiced">\s*15\s*<\/strong>/');
        $this->assertResponseContains('/badges/stock-transactions/' . $id);
    }

    public function testStockTransactionsShowsDetailedBadgeLedger(): void
    {
        $id = 'f525eb6d-021c-4ef2-811f-feac8db8d35d';

        $this->get("/badges/stock-transactions/{$id}");

        $this->assertResponseOk();
        $this->assertResponseContains('Stock Transactions: Lorem ipsum dolor sit amet');
        $this->assertResponseContains('badge-product-card');
        $this->assertResponseContains('All transaction types');
        $this->assertResponseContains('Rep. Order');
        $this->assertResponseContains('Rep. Receipt');
        $this->assertResponseNotContains('Replenishment Order');
        $this->assertResponseNotContains('Replenishment Receipt');
        $this->assertResponseContains('Audit E/A');
        $this->assertResponseRegExp(
            '#href="/replenishments/view/f6d1f429-877b-4d92-83a0-cb305d853da7"'
            . '>REP-2026-02-01</a>#',
        );
        $this->assertResponseRegExp(
            '#href="/audits/view/003b39f5-34f6-4f49-b1ff-97204ffc4336"'
            . '>AUD-2026-02-01</a>#',
        );
        foreach (TransactionType::cases() as $type) {
            if (
                in_array(
                    $type,
                    [TransactionType::ReplenishmentOrder, TransactionType::ReplenishmentReceipt],
                    true,
                )
            ) {
                continue;
            }
            $this->assertResponseContains($type->label());
        }
        $this->assertSame(5, substr_count((string)$this->_response->getBody(), '<tr>'));
    }

    public function testStockTransactionsFiltersByTransactionType(): void
    {
        $id = 'f525eb6d-021c-4ef2-811f-feac8db8d35d';

        $this->get(
            "/badges/stock-transactions/{$id}?transaction_type="
            . TransactionType::ReplenishmentOrder->value,
        );

        $this->assertResponseOk();
        $this->assertSame(2, substr_count((string)$this->_response->getBody(), '<tr>'));
        $this->assertResponseContains('£1.50');
    }

    /**
     * Test add method
     *
     * @return void
     * @link \App\Controller\BadgesController::add()
     */
    public function testAdd(): void
    {
        $badges = $this->getTableLocator()->get('Badges');
        $before = $badges->find()->count();

        $this->enableCsrfToken();
        $this->post('/badges/add', [
            'badge_name' => 'New Badge',
            'stocked' => true,
            'national_product_code' => null,
            'national_data' => null,
            'reserve_quantity' => 6,
            'price' => '4.50',
            'replenishment_price' => '2.75',
        ]);

        $this->assertRedirect(['controller' => 'Badges', 'action' => 'index']);
        $this->assertFlashMessage('The badge has been saved.');
        $this->assertSame($before + 1, $badges->find()->count());

        $saved = $badges->find()
            ->where(['badge_name' => 'New Badge'])
            ->firstOrFail();
        $this->assertTrue((bool)$saved->stocked);
        $this->assertNull($saved->national_product_code);
        $this->assertNull($saved->national_data);
        $this->assertSame(6, $saved->reserve_quantity);
        $this->assertSame(4.5, (float)$saved->price);
        $this->assertSame(2.75, (float)$saved->replenishment_price);
    }

    public function testAddAssociatesSelectedTags(): void
    {
        $this->enableCsrfToken();
        $this->post('/badges/add', [
            'badge_name' => 'Tagged Badge',
            'stocked' => true,
            'reserve_quantity' => 0,
            'price' => '4.50',
            'replenishment_price' => '2.75',
            'badge_tags' => [
                '_ids' => [
                    '3bb4858e-83d2-4ddd-8f72-bab835e05a2d',
                    '1cc59bf0-37b3-42db-a5af-47745ac381d5',
                ],
            ],
        ]);

        $this->assertRedirect(['controller' => 'Badges', 'action' => 'index']);

        $badge = $this->getTableLocator()->get('Badges')
            ->find()
            ->where(['badge_name' => 'Tagged Badge'])
            ->contain(['BadgeTags'])
            ->firstOrFail();
        $this->assertCount(2, $badge->badge_tags);
    }

    public function testAddDisplaysTagsGroupedByCategory(): void
    {
        $this->get('/badges/add');

        $this->assertResponseOk();
        $this->assertResponseContains(TagCategory::Sections->label());
        $this->assertResponseContains(TagCategory::BadgeTypes->label());
        $this->assertResponseContains('Beavers');
        $this->assertResponseContains('Activity Badge');
        $this->assertResponseContains('badge_tags[_ids][]');
    }

    /**
     * Test edit method
     *
     * @return void
     * @link \App\Controller\BadgesController::edit()
     */
    public function testEdit(): void
    {
        $badges = $this->getTableLocator()->get('Badges');
        $id = 'f525eb6d-021c-4ef2-811f-feac8db8d35d';

        $this->enableCsrfToken();
        $this->put("/badges/edit/{$id}", [
            'badge_name' => 'Updated Badge',
            'stocked' => false,
            'national_product_code' => null,
            'national_data' => null,
            'reserve_quantity' => 7,
            'price' => '5.25',
            'replenishment_price' => '3.25',
        ]);

        $this->assertRedirect(['controller' => 'Badges', 'action' => 'index']);
        $this->assertFlashMessage('The badge has been saved.');

        $updated = $badges->get($id);
        $this->assertSame('Updated Badge', $updated->badge_name);
        $this->assertFalse((bool)$updated->stocked);
        $this->assertSame(7, $updated->reserve_quantity);
        $this->assertSame(5.25, (float)$updated->price);
        $this->assertSame(3.25, (float)$updated->replenishment_price);
    }

    public function testEditUnlistedBadgeExposesAndSavesImageUrl(): void
    {
        $id = '0f3b8a4a-6c12-4f12-9a2e-0d9e4e4b2f70';

        $this->get("/badges/edit/{$id}");
        $this->assertResponseOk();
        $this->assertResponseContains('badge-product-card');
        $this->assertResponseNotContains('Image URL');

        $this->get("/badges/edit/{$id}?unlisted=true");
        $this->assertResponseOk();
        $this->assertResponseContains('Image URL');
        $this->assertResponseContains('name="image_url"');

        $this->enableCsrfToken();
        $this->put("/badges/edit/{$id}?unlisted=true", [
            'badge_name' => 'Second badge',
            'national_product_code' => null,
            'image_url' => 'https://example.com/unlisted-badge.jpg',
            'stocked' => true,
            'reserve_quantity' => 0,
            'price' => '2.50',
            'replenishment_price' => '1.50',
        ]);

        $this->assertRedirect(['controller' => 'Badges', 'action' => 'index']);
        $badge = $this->getTableLocator()->get('Badges')->get($id);
        $this->assertSame('https://example.com/unlisted-badge.jpg', $badge->image_url);
        $this->assertSame($badge->image_url, $badge->image_medium_url);
    }

    public function testEditReplacesSelectedTags(): void
    {
        $badges = $this->getTableLocator()->get('Badges');
        $id = 'f525eb6d-021c-4ef2-811f-feac8db8d35d';

        $this->enableCsrfToken();
        $this->put("/badges/edit/{$id}", [
            'badge_name' => 'Updated Badge',
            'stocked' => true,
            'reserve_quantity' => 2,
            'price' => '5.25',
            'replenishment_price' => '3.25',
            'badge_tags' => [
                '_ids' => ['1cc59bf0-37b3-42db-a5af-47745ac381d5'],
            ],
        ]);

        $this->assertRedirect(['controller' => 'Badges', 'action' => 'index']);

        $updated = $badges->get($id, contain: ['BadgeTags']);
        $this->assertCount(1, $updated->badge_tags);
        $this->assertSame('Activity Badge', $updated->badge_tags[0]->tag_name);
    }

    public function testEditDisplaysExistingTagAsSelected(): void
    {
        $id = 'f525eb6d-021c-4ef2-811f-feac8db8d35d';

        $this->get("/badges/edit/{$id}");

        $this->assertResponseOk();
        $this->assertResponseRegExp(
            '/value="3bb4858e-83d2-4ddd-8f72-bab835e05a2d"[^>]*checked="checked"/',
        );
    }

    public function testEditCanClearAllTags(): void
    {
        $badges = $this->getTableLocator()->get('Badges');
        $id = 'f525eb6d-021c-4ef2-811f-feac8db8d35d';

        $this->enableCsrfToken();
        $this->put("/badges/edit/{$id}", [
            'badge_name' => 'Updated Badge',
            'stocked' => true,
            'reserve_quantity' => 2,
            'price' => '5.25',
            'replenishment_price' => '3.25',
            'badge_tags' => ['_ids' => ''],
        ]);

        $this->assertRedirect(['controller' => 'Badges', 'action' => 'index']);
        $this->assertSame(
            [],
            $badges->get($id, contain: ['BadgeTags'])->badge_tags,
        );
    }

    public function testActivateUnstockedBadge(): void
    {
        $badges = $this->getTableLocator()->get('Badges');
        $id = '0f3b8a4a-6c12-4f12-9a2e-0d9e4e4b2f70';
        $badges->updateAll([
            'stocked' => false,
            'status' => BadgeStatus::Unstocked->value,
        ], ['id' => $id]);

        $this->enableCsrfToken();
        $this->post("/badges/activate/{$id}");

        $this->assertRedirect(['controller' => 'Badges', 'action' => 'index']);
        $this->assertFlashMessage('The badge is now stocked.');

        $updated = $badges->get($id);
        $this->assertTrue($updated->stocked);
        $this->assertSame(BadgeStatus::Unavailable, $updated->status);
    }

    public function testActivatePreservesFiltersOnRedirect(): void
    {
        $badges = $this->getTableLocator()->get('Badges');
        $id = '0f3b8a4a-6c12-4f12-9a2e-0d9e4e4b2f70';
        $badges->updateAll([
            'stocked' => false,
            'status' => BadgeStatus::Unstocked->value,
        ], ['id' => $id]);

        $this->enableCsrfToken();
        $this->post("/badges/activate/{$id}?name=Second&status=40");

        $this->assertRedirect('/badges?name=Second&status=40');
    }

    public function testActivateRejectsBadgeThatIsNotUnstocked(): void
    {
        $badges = $this->getTableLocator()->get('Badges');
        $id = 'f525eb6d-021c-4ef2-811f-feac8db8d35d';

        $this->enableCsrfToken();
        $this->post("/badges/activate/{$id}");

        $this->assertRedirect(['controller' => 'Badges', 'action' => 'index']);
        $this->assertFlashMessage('Only unstocked badges can be activated.');
        $this->assertTrue($badges->get($id)->stocked);
    }

    public function testActivateRequiresPost(): void
    {
        $id = '0f3b8a4a-6c12-4f12-9a2e-0d9e4e4b2f70';

        $this->get("/badges/activate/{$id}");

        $this->assertResponseCode(405);
    }

    /**
     * Test delete method
     *
     * @return void
     * @link \App\Controller\BadgesController::delete()
     */
    public function testDelete(): void
    {
        $badges = $this->getTableLocator()->get('Badges');
        $entity = $badges->newEntity([
            'badge_name' => 'Delete Badge',
            'stocked' => true,
            'national_product_code' => null,
            'national_data' => null,
            'price' => '2.00',
            'replenishment_price' => '1.00',
        ]);
        $badges->saveOrFail($entity);
        $id = $entity->id;
        $before = $badges->find()->count();

        $this->enableCsrfToken();
        $this->post("/badges/delete/{$id}");

        $this->assertRedirect(['controller' => 'Badges', 'action' => 'index']);
        $this->assertFlashMessage('The badge has been deleted.');
        $this->assertSame($before - 1, $badges->find()->count());
        $this->assertFalse($badges->exists(['id' => $id]));
    }

    public function testDeleteRejectsBadgeWithStockHistory(): void
    {
        $badges = $this->getTableLocator()->get('Badges');
        $id = 'f525eb6d-021c-4ef2-811f-feac8db8d35d';

        $this->enableCsrfToken();
        $this->post("/badges/delete/{$id}");

        $this->assertRedirect(['controller' => 'Badges', 'action' => 'index']);
        $this->assertFlashMessage(
            'Badges with receipted or fulfilled stock history cannot be deleted.',
        );
        $this->assertTrue($badges->exists(['id' => $id]));
    }
}
