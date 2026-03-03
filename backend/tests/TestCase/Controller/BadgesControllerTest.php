<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\BadgesController;
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

        $this->get("/badges/view/{$id}");
        $this->assertResponseOk();
        $this->assertResponseContains('Lorem ipsum dolor sit amet');
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
        ]);

        $this->assertRedirect(['controller' => 'Badges', 'action' => 'index']);
        $this->assertFlashMessage('The badge has been saved.');

        $updated = $badges->get($id);
        $this->assertSame('Updated Badge', $updated->badge_name);
        $this->assertFalse((bool)$updated->stocked);
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
}
