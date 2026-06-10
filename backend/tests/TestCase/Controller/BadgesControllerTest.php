<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Model\Enum\BadgeStatus;
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
        'app.Badges',
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
        $this->assertResponseContains('All statuses');
        $this->assertResponseNotContains(
            '/badges/delete/f525eb6d-021c-4ef2-811f-feac8db8d35d',
        );
        $this->assertResponseContains(
            '/badges/delete/0f3b8a4a-6c12-4f12-9a2e-0d9e4e4b2f70',
        );
    }

    public function testIndexFilters(): void
    {
        $this->get('/badges?name=Lorem&status=' . BadgeStatus::Available->value);
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

    public function testIndexShowsStockActionOnlyForUnstockedBadges(): void
    {
        $badges = $this->getTableLocator()->get('Badges');
        $unstocked = $badges->get('0f3b8a4a-6c12-4f12-9a2e-0d9e4e4b2f70');
        $unstocked->set('stocked', false);
        $badges->saveOrFail($unstocked, ['skipAlgolia' => true]);

        $this->get('/badges');

        $this->assertResponseOk();
        $this->assertResponseContains('/badges/activate/' . $unstocked->id);
        $this->assertResponseRegExp('/<a[^>]*>Stock<\/a>/');
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
            ],
            ['id' => $id],
        );

        $this->get("/badges/view/{$id}");
        $this->assertResponseOk();
        $this->assertResponseContains('Lorem ipsum dolor sit amet');
        $this->assertResponseContains('Available');
        $this->assertResponseContains('Stock Amounts');
        $this->assertResponseContains('Calculated Stock');
        $this->assertResponseContains('Historic Stock Movements');
        $this->assertResponseRegExp('/data-stock-amount="on-hand">\s*11\s*<\/strong>/');
        $this->assertResponseRegExp('/data-stock-amount="reserve">\s*10\s*<\/strong>/');
        $this->assertResponseRegExp('/data-stock-amount="pending">\s*12\s*<\/strong>/');
        $this->assertResponseRegExp('/data-stock-amount="receipted">\s*13\s*<\/strong>/');
        $this->assertResponseRegExp('/data-stock-amount="fulfilled">\s*14\s*<\/strong>/');
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
