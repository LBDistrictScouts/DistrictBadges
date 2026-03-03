<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\AuditLinesController Test Case
 *
 * @link \App\Controller\AuditLinesController
 */
class AuditLinesControllerTest extends TestCase
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
        'app.AuditLines',
    ];

    /**
     * Test index method
     *
     * @return void
     * @link \App\Controller\AuditLinesController::index()
     */
    public function testIndex(): void
    {
        $this->get('/audit-lines');
        $this->assertResponseOk();
        $this->assertResponseContains('Audit line hash');
    }

    /**
     * Test view method
     *
     * @return void
     * @link \App\Controller\AuditLinesController::view()
     */
    public function testView(): void
    {
        $this->get('/audit-lines/view/1d2e3f40-1111-4b2a-8c3d-0a1b2c3d4e5f');
        $this->assertResponseOk();
        $this->assertResponseContains('Audit line hash');
    }

    /**
     * Test add method
     *
     * @return void
     * @link \App\Controller\AuditLinesController::add()
     */
    public function testAdd(): void
    {
        $lines = $this->getTableLocator()->get('AuditLines');
        $before = $lines->find()->count();

        $this->enableCsrfToken();
        $this->post('/audit-lines/add', [
            'badge_id' => 'f525eb6d-021c-4ef2-811f-feac8db8d35d',
            'audit_id' => '003b39f5-34f6-4f49-b1ff-97204ffc4336',
            'on_hand_quantity_change' => 1,
            'receipted_quantity_change' => 0,
            'pending_quantity_change' => 0,
        ]);

        $this->assertRedirect(['controller' => 'AuditLines', 'action' => 'index']);
        $this->assertFlashMessage('The audit line has been saved.');
        $this->assertSame($before + 1, $lines->find()->count());

        $created = $lines->find()->orderBy(['transaction_timestamp' => 'DESC', 'id' => 'DESC'])->firstOrFail();
        $this->assertSame(0, $created->get('transaction_type')->value);
    }

    /**
     * Test edit method
     *
     * @return void
     * @link \App\Controller\AuditLinesController::edit()
     */
    public function testEdit(): void
    {
        $lines = $this->getTableLocator()->get('AuditLines');
        $id = '1d2e3f40-1111-4b2a-8c3d-0a1b2c3d4e5f';

        $this->enableCsrfToken();
        $this->put("/audit-lines/edit/{$id}", [
            'badge_id' => 'f525eb6d-021c-4ef2-811f-feac8db8d35d',
            'audit_id' => '003b39f5-34f6-4f49-b1ff-97204ffc4336',
            'on_hand_quantity_change' => 5,
            'receipted_quantity_change' => 1,
            'pending_quantity_change' => 0,
        ]);

        $this->assertRedirect(['controller' => 'AuditLines', 'action' => 'index']);
        $this->assertFlashMessage('The audit line has been saved.');

        /** @var AuditLine $updated */
        $updated = $lines->get($id);
        $this->assertSame(5, (int)$updated->on_hand_quantity_change);
        $this->assertSame(0, $updated->get('transaction_type')->value);
    }

    /**
     * Test delete method
     *
     * @return void
     * @link \App\Controller\AuditLinesController::delete()
     */
    public function testDelete(): void
    {
        $lines = $this->getTableLocator()->get('AuditLines');
        $before = $lines->find()->count();

        $this->enableCsrfToken();
        $this->post('/audit-lines/delete/1d2e3f40-1111-4b2a-8c3d-0a1b2c3d4e5f');

        $this->assertRedirect(['controller' => 'AuditLines', 'action' => 'index']);
        $this->assertFlashMessage('The audit line has been deleted.');
        $this->assertSame($before - 1, $lines->find()->count());
    }
}
