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
        'app.AuditLines',
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
        $this->assertResponseContains('Audit Number');
        $this->assertResponseContains('AUD-2026-02-01');
        $this->assertResponseContains('Lorem ipsum dolor sit amet');
        $this->assertResponseContains('data-badge-index-controls');
        $this->assertResponseContains('All users');
        $this->assertResponseContains('All statuses');
        $this->assertResponseNotContains('>Edit<');
    }

    public function testIndexFilters(): void
    {
        $this->get('/audits?number=AUD-2026&completed=1');
        $this->assertResponseOk();
        $this->assertSame(2, substr_count((string)$this->_response->getBody(), '<tr>'));

        $this->get('/audits?completed=0');
        $this->assertResponseOk();
        $this->assertSame(1, substr_count((string)$this->_response->getBody(), '<tr>'));

        $this->get('/audits?audited_from=2030-01-01');
        $this->assertResponseOk();
        $this->assertSame(1, substr_count((string)$this->_response->getBody(), '<tr>'));
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
        $this->assertResponseContains('Stock audit AUD-2026-02-01');
        $this->assertResponseContains('Lorem ipsum dolor sit amet');
        $this->assertResponseNotContains('Edit Audit');
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

        $saved = $audits->find()
            ->where(['user_id' => '30350fc5-a8b7-4b3e-85ae-9f2f5f3a30e1'])
            ->orderByDesc('audit_timestamp')
            ->firstOrFail();
        $this->assertRedirect(['controller' => 'Audits', 'action' => 'view', $saved->id]);
        $this->assertFlashMessage('The audit has started.');
        $this->assertSame($before + 1, $audits->find()->count());
        $this->assertSame('30350fc5-a8b7-4b3e-85ae-9f2f5f3a30e1', $saved->user_id);
        $this->assertFalse($saved->audit_completed);
        $this->assertMatchesRegularExpression('/^AUD-\d{4}-\d{2}-\d+$/', $saved->audit_number);
    }

    public function testCannotBeginSecondAuditWhileOneIsOpen(): void
    {
        $audits = $this->getTableLocator()->get('Audits');
        $openAudit = $audits->newEntity([
            'user_id' => '30350fc5-a8b7-4b3e-85ae-9f2f5f3a30e1',
            'audit_completed' => false,
        ]);
        $audits->saveOrFail($openAudit);
        $before = $audits->find()->count();

        $this->enableCsrfToken();
        $this->post('/audits/add', [
            'user_id' => '30350fc5-a8b7-4b3e-85ae-9f2f5f3a30e1',
        ]);

        $this->assertRedirect(['controller' => 'Audits', 'action' => 'view', $openAudit->id]);
        $this->assertSame($before, $audits->find()->count());
        $this->assertFlashMessage('An audit is already open. Complete it before beginning another.');
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
            'audit_completed' => false,
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

    public function testAuditWithLinesCannotBeDeleted(): void
    {
        $audits = $this->getTableLocator()->get('Audits');
        $audit = $audits->get('003b39f5-34f6-4f49-b1ff-97204ffc4336');
        $audit->audit_completed = false;
        $audits->saveOrFail($audit);

        $this->enableCsrfToken();
        $this->post('/audits/delete/' . $audit->id);

        $this->assertResponseCode(405);
        $this->assertTrue($audits->exists(['id' => $audit->id]));
    }

    public function testOpenAuditCountDoesNotApplyUntilCompletionAndThenLocks(): void
    {
        $audits = $this->getTableLocator()->get('Audits');
        $badges = $this->getTableLocator()->get('Badges');
        $lines = $this->getTableLocator()->get('AuditLines');
        $audit = $audits->newEntity([
            'user_id' => '30350fc5-a8b7-4b3e-85ae-9f2f5f3a30e1',
            'audit_completed' => false,
        ]);
        $audits->saveOrFail($audit);
        $badgeId = '0f3b8a4a-6c12-4f12-9a2e-0d9e4e4b2f70';

        $this->enableCsrfToken();
        $this->post('/audits/count/' . $audit->id, [
            'badge_id' => $badgeId,
            'actual_quantity' => 7,
        ]);
        $this->assertRedirect(['controller' => 'Audits', 'action' => 'view', $audit->id]);
        $this->assertSame(0, (int)$badges->get($badgeId)->on_hand_quantity);
        $line = $lines->find()->where(['audit_id' => $audit->id, 'badge_id' => $badgeId])->firstOrFail();
        $this->assertSame(0, (int)$line->audit_expected_quantity);
        $this->assertSame(7, (int)$line->audit_actual_quantity);
        $this->assertSame(7, (int)$line->on_hand_quantity_change);

        $this->enableCsrfToken();
        $this->post('/audits/complete/' . $audit->id);
        $this->assertRedirect(['controller' => 'Audits', 'action' => 'view', $audit->id]);
        $this->assertTrue($audits->get($audit->id)->audit_completed);
        $this->assertSame(7, (int)$badges->get($badgeId)->on_hand_quantity);

        $this->enableCsrfToken();
        $this->post('/audits/count/' . $audit->id, [
            'badge_id' => $badgeId,
            'actual_quantity' => 9,
        ]);
        $this->assertResponseCode(405);
        $this->assertSame(7, (int)$lines->get($line->id)->audit_actual_quantity);
    }

    public function testOpenAuditCanStockAnUnstockedBadge(): void
    {
        $audits = $this->getTableLocator()->get('Audits');
        $badges = $this->getTableLocator()->get('Badges');
        $audit = $audits->newEntity([
            'user_id' => '30350fc5-a8b7-4b3e-85ae-9f2f5f3a30e1',
            'audit_completed' => false,
        ]);
        $audits->saveOrFail($audit);
        $badge = $badges->get('0f3b8a4a-6c12-4f12-9a2e-0d9e4e4b2f70');
        $badge->stocked = false;
        $badges->saveOrFail($badge);

        $this->enableCsrfToken();
        $this->post('/audits/stock-badge/' . $audit->id, ['badge_id' => $badge->id]);

        $this->assertRedirect(['controller' => 'Audits', 'action' => 'view', $audit->id]);
        $this->assertTrue($badges->get($badge->id)->stocked);
        $this->assertFlashMessage('Second badge is now stocked and ready to count.');
    }
}
