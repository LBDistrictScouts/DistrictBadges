<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\FulfilmentLinesController;
use App\Model\Entity\FulfilmentLine;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\FulfilmentLinesController Test Case
 *
 * @link \App\Controller\FulfilmentLinesController
 */
class FulfilmentLinesControllerTest extends TestCase
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
        'app.Badges',
        'app.Fulfilments',
        'app.Audits',
        'app.Replenishments',
        'app.FulfilmentLines',
    ];

    /**
     * Test index method
     *
     * @return void
     * @link \App\Controller\FulfilmentLinesController::index()
     */
    public function testIndex(): void
    {
        $this->get('/fulfilment-lines');
        $this->assertResponseOk();
        $this->assertResponseContains('Fulfilment line hash');
    }

    /**
     * Test view method
     *
     * @return void
     * @link \App\Controller\FulfilmentLinesController::view()
     */
    public function testView(): void
    {
        $this->get('/fulfilment-lines/view/2e3f4051-2222-4c3b-9d4e-1b2c3d4e5f60');
        $this->assertResponseOk();
        $this->assertResponseContains('Fulfilment line hash');
    }

    /**
     * Test add method
     *
     * @return void
     * @link \App\Controller\FulfilmentLinesController::add()
     */
    public function testAdd(): void
    {
        $lines = $this->getTableLocator()->get('FulfilmentLines');
        $before = $lines->find()->count();

        $this->enableCsrfToken();
        $this->post('/fulfilment-lines/add', [
            'badge_id' => 'f525eb6d-021c-4ef2-811f-feac8db8d35d',
            'fulfilment_id' => 'be5a0a9f-9d87-4191-b819-b7e1c1c50a3a',
            'on_hand_quantity_change' => 1,
            'receipted_quantity_change' => 0,
            'pending_quantity_change' => 0,
        ]);

        $this->assertRedirect(['controller' => 'FulfilmentLines', 'action' => 'index']);
        $this->assertFlashMessage('The fulfilment line has been saved.');
        $this->assertSame($before + 1, $lines->find()->count());

        $created = $lines->find()->orderBy(['transaction_timestamp' => 'DESC', 'id' => 'DESC'])->firstOrFail();
        $this->assertSame(2, $created->get('transaction_type')->value);
    }

    /**
     * Test edit method
     *
     * @return void
     * @link \App\Controller\FulfilmentLinesController::edit()
     */
    public function testEdit(): void
    {
        $lines = $this->getTableLocator()->get('FulfilmentLines');
        $id = '2e3f4051-2222-4c3b-9d4e-1b2c3d4e5f60';

        $this->enableCsrfToken();
        $this->put("/fulfilment-lines/edit/{$id}", [
            'badge_id' => 'f525eb6d-021c-4ef2-811f-feac8db8d35d',
            'fulfilment_id' => 'be5a0a9f-9d87-4191-b819-b7e1c1c50a3a',
            'on_hand_quantity_change' => 4,
            'receipted_quantity_change' => 2,
            'pending_quantity_change' => 1,
        ]);

        $this->assertRedirect(['controller' => 'FulfilmentLines', 'action' => 'index']);
        $this->assertFlashMessage('The fulfilment line has been saved.');

        /** @var $updated FulfilmentLine */
        $updated = $lines->get($id);
        $this->assertSame(4, (int)$updated->on_hand_quantity_change);
        $this->assertSame(2, $updated->get('transaction_type')->value);
    }

    /**
     * Test delete method
     *
     * @return void
     * @link \App\Controller\FulfilmentLinesController::delete()
     */
    public function testDelete(): void
    {
        $lines = $this->getTableLocator()->get('FulfilmentLines');
        $before = $lines->find()->count();

        $this->enableCsrfToken();
        $this->post('/fulfilment-lines/delete/2e3f4051-2222-4c3b-9d4e-1b2c3d4e5f60');

        $this->assertRedirect(['controller' => 'FulfilmentLines', 'action' => 'index']);
        $this->assertFlashMessage('The fulfilment line has been deleted.');
        $this->assertSame($before - 1, $lines->find()->count());
    }
}
