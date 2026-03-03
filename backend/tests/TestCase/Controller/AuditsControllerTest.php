<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\AuditsController Test Case
 *
 * @link \App\Controller\AuditsController
 */
class AuditsControllerTest extends TestCase
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
        'app.Fulfilments',
        'app.Replenishments',
        'app.StockTransactions',
    ];

    /**
     * Test index method
     *
     * @return void
     * @link \App\Controller\AuditsController::index()
     */
    public function testIndex(): void
    {
        $this->get('/audits');
        $this->assertResponseOk();
        $this->assertResponseContains('Lorem ipsum dolor sit amet');
    }

    /**
     * Test view method
     *
     * @return void
     * @link \App\Controller\AuditsController::view()
     */
    public function testView(): void
    {
        $this->get('/audits/view/003b39f5-34f6-4f49-b1ff-97204ffc4336');
        $this->assertResponseOk();
        $this->assertResponseContains('Lorem ipsum dolor sit amet');
    }

    /**
     * Test add method
     *
     * @return void
     * @link \App\Controller\AuditsController::add()
     */
    public function testAdd(): void
    {
        $audits = $this->getTableLocator()->get('Audits');
        $before = $audits->find()->count();

        $this->enableCsrfToken();
        $this->post('/audits/add', [
            'user_id' => '30350fc5-a8b7-4b3e-85ae-9f2f5f3a30e1',
            'audit_timestamp' => '2026-02-22 10:00:00',
            'audit_completed' => true,
        ]);

        $this->assertRedirect(['controller' => 'Audits', 'action' => 'index']);
        $this->assertFlashMessage('The audit has been saved.');
        $this->assertSame($before + 1, $audits->find()->count());

        $saved = $audits->find()
            ->where(['audit_timestamp' => '2026-02-22 10:00:00'])
            ->firstOrFail();
        $this->assertSame('30350fc5-a8b7-4b3e-85ae-9f2f5f3a30e1', $saved->user_id);
    }

    /**
     * Test edit method
     *
     * @return void
     * @link \App\Controller\AuditsController::edit()
     */
    public function testEdit(): void
    {
        $audits = $this->getTableLocator()->get('Audits');
        $id = '003b39f5-34f6-4f49-b1ff-97204ffc4336';

        $this->enableCsrfToken();
        $this->put("/audits/edit/{$id}", [
            'user_id' => '30350fc5-a8b7-4b3e-85ae-9f2f5f3a30e1',
            'audit_timestamp' => '2026-02-22 11:00:00',
            'audit_completed' => false,
        ]);

        $this->assertRedirect(['controller' => 'Audits', 'action' => 'index']);
        $this->assertFlashMessage('The audit has been saved.');

        $updated = $audits->get($id);
        $this->assertSame(false, $updated->audit_completed);
    }

    /**
     * Test delete method
     *
     * @return void
     * @link \App\Controller\AuditsController::delete()
     */
    public function testDelete(): void
    {
        $audits = $this->getTableLocator()->get('Audits');
        $entity = $audits->newEntity([
            'user_id' => '30350fc5-a8b7-4b3e-85ae-9f2f5f3a30e1',
            'audit_timestamp' => '2026-02-22 12:00:00',
            'audit_completed' => true,
        ]);
        $audits->saveOrFail($entity);
        $id = $entity->id;
        $before = $audits->find()->count();

        $this->enableCsrfToken();
        $this->post("/audits/delete/{$id}");

        $this->assertRedirect(['controller' => 'Audits', 'action' => 'index']);
        $this->assertFlashMessage('The audit has been deleted.');
        $this->assertSame($before - 1, $audits->find()->count());
        $this->assertFalse($audits->exists(['id' => $id]));
    }
}
