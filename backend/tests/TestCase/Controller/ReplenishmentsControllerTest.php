<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Model\Enum\OrderStatus;
use App\Model\Enum\ReplenishmentStatus;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\ReplenishmentsController Test Case
 *
 * @link \App\Controller\ReplenishmentsController
 */
class ReplenishmentsControllerTest extends TestCase
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
        'app.Orders',
        'app.OrderLines',
        'app.StockTransactions',
    ];

    /**
     * Test index method
     *
     * @return void
     * @link \App\Controller\ReplenishmentsController::index()
     */
    public function testIndex(): void
    {
        $this->get('/replenishments');
        $this->assertResponseOk();
        $this->assertResponseNotContains('>Delete<');
        $this->assertResponseContains('REP-2026-02-01');
        $this->assertResponseContains('Ord. Qty');
        $this->assertResponseContains('Ord. Value');
        $this->assertResponseContains('Rec. Qty');
        $this->assertResponseContains('Rec. Value');
        $this->assertResponseContains('Received');
        $this->assertResponseContains('All statuses');
        $this->assertResponseContains('Created From');
        $this->assertResponseContains('Created To');
    }

    public function testIndexSortingOverridesDefaultOrder(): void
    {
        $this->getTableLocator()->get('Replenishments')->getConnection()->insertQuery()
            ->insert(['id', 'replenishment_number', 'created_date', 'status'])
            ->into('replenishments')
            ->values([
                'id' => 'f519b9b5-c5ca-46e0-ad6b-2d018387bb13',
                'replenishment_number' => 'AAA-sortable-replenishment',
                'created_date' => '2020-01-01 00:00:00',
                'status' => ReplenishmentStatus::Draft->value,
            ])
            ->execute();

        $this->get('/replenishments');
        $defaultBody = (string)$this->_response->getBody();
        $this->assertLessThan(
            strpos($defaultBody, 'AAA-sortable-replenishment'),
            strpos($defaultBody, 'REP-2026-02-01'),
        );

        $this->get('/replenishments?sort=replenishment_number&direction=asc');
        $sortedBody = (string)$this->_response->getBody();
        $this->assertLessThan(
            strpos($sortedBody, 'REP-2026-02-01'),
            strpos($sortedBody, 'AAA-sortable-replenishment'),
        );
    }

    public function testIndexFiltersByStatus(): void
    {
        $this->get('/replenishments?status=' . ReplenishmentStatus::Received->value);
        $this->assertResponseOk();
        $this->assertResponseContains('REP-2026-02-01');

        $this->get('/replenishments?status=' . ReplenishmentStatus::Submitted->value);
        $this->assertResponseOk();
        $this->assertResponseNotContains('REP-2026-02-01');
    }

    public function testIndexFiltersByNumberAndDate(): void
    {
        $this->get('/replenishments?number=REP-2026');
        $this->assertResponseOk();
        $this->assertResponseContains('REP-2026-02-01');

        $this->get('/replenishments?number=Missing');
        $this->assertResponseOk();
        $this->assertResponseNotContains('REP-2026-02-01');

        $this->get('/replenishments?created_from=2030-01-01');
        $this->assertResponseOk();
        $this->assertResponseNotContains('REP-2026-02-01');
    }

    public function testIndexPaginationPreservesFalseyStatusFilter(): void
    {
        $replenishments = $this->getTableLocator()->get('Replenishments');
        for ($index = 0; $index < 10; $index++) {
            $replenishments->saveOrFail($replenishments->newEmptyEntity());
        }
        $replenishments->updateAll(['status' => ReplenishmentStatus::Draft->value], []);

        $this->get('/replenishments?status=0&limit=10');

        $this->assertResponseOk();
        $this->assertResponseContains('Page 1 of 2');
        $this->assertResponseRegExp(
            '/href="(?=[^"]*page=2)(?=[^"]*status=0)[^"]+"[^>]*>2<\/a>/',
        );
    }

    /**
     * Test view method
     *
     * @return void
     * @link \App\Controller\ReplenishmentsController::view()
     */
    public function testView(): void
    {
        $this->get('/replenishments/view/f6d1f429-877b-4d92-83a0-cb305d853da7');
        $this->assertResponseOk();
        $this->assertResponseContains('REP-2026-02-01');
        $this->assertResponseContains('Ordered Lines');
        $this->assertResponseContains('Received Lines');
        $this->assertResponseNotContains('Transaction Type');
    }

    /**
     * Test add method
     *
     * @return void
     * @link \App\Controller\ReplenishmentsController::add()
     */
    public function testAdd(): void
    {
        $replenishments = $this->getTableLocator()->get('Replenishments');
        $before = $replenishments->find()->count();

        $this->enableCsrfToken();
        $this->post('/replenishments/add', [
            'created_date' => '2026-02-22 10:00:00',
            'order_submitted' => true,
            'order_submitted_date' => '2026-02-22 10:00:00',
            'received' => true,
            'received_date' => '2026-02-22 10:00:00',
            'total_ordered_amount' => 12.5,
            'total_ordered_quantity' => 5,
            'total_received_amount' => 10.5,
            'total_received_quantity' => 4,
            'replenishment_number' => 'WO-NEW',
            'wholesaler_order_number' => 'SUP-67890',
            'replenishment_order_lines' => [
                [
                    'badge_id' => 'f525eb6d-021c-4ef2-811f-feac8db8d35d',
                    'quantity' => 5,
                    'unit_price' => '2.50',
                    'monetary_amount' => '999.99',
                ],
            ],
        ]);

        $this->assertRedirect(['controller' => 'Replenishments', 'action' => 'index']);
        $this->assertFlashMessage('The replenishment has been saved.');
        $this->assertSame($before + 1, $replenishments->find()->count());

        $saved = $replenishments->find()
            ->where(['replenishment_number LIKE' => 'REP-%'])
            ->orderByDesc('created_date')
            ->firstOrFail();
        $this->assertMatchesRegularExpression('/^REP-\d{4}-\d{2}-\d+$/', $saved->replenishment_number);
        $this->assertSame(ReplenishmentStatus::Submitted, $saved->status);
        $this->assertTrue($saved->order_submitted);
        $this->assertNotNull($saved->order_submitted_date);
        $this->assertFalse($saved->received);
        $this->assertSame(12.5, (float)$saved->total_ordered_amount);
        $this->assertSame(5, $saved->total_ordered_quantity);
        $this->assertSame('SUP-67890', $saved->wholesaler_order_number);

        $line = $replenishments->ReplenishmentOrderLines->find()
            ->where(['replenishment_id' => $saved->id])
            ->firstOrFail();
        $this->assertSame('f525eb6d-021c-4ef2-811f-feac8db8d35d', $line->badge_id);
        $this->assertSame(5, $line->pending_quantity_change);
        $this->assertSame(12.5, (float)$line->monetary_amount);
        $this->assertSame(2.5, (float)$line->unit_price);
    }

    public function testEditOnlyChangesWholesalerOrderNumberWhenUnreceived(): void
    {
        $id = 'f6d1f429-877b-4d92-83a0-cb305d853da7';
        $replenishments = $this->getTableLocator()->get('Replenishments');
        $replenishments->updateAll([
            'received' => false,
            'received_date' => null,
            'status' => ReplenishmentStatus::Submitted->value,
        ], ['id' => $id]);
        $before = $replenishments->get($id);

        $this->get("/replenishments/edit/{$id}");
        $this->assertResponseOk();
        $this->assertResponseContains('Edit Wholesaler Order Number');
        $this->assertResponseContains('name="wholesaler_order_number"');
        $this->assertResponseNotContains('name="total_ordered_amount"');

        $this->enableCsrfToken();
        $this->post("/replenishments/edit/{$id}", [
            'wholesaler_order_number' => 'SUP-UPDATED',
            'total_ordered_amount' => '999.99',
            'status' => ReplenishmentStatus::Cancelled->value,
        ]);

        $this->assertRedirect(['controller' => 'Replenishments', 'action' => 'view', $id]);
        $this->assertFlashMessage('The wholesaler order number has been saved.');
        $updated = $replenishments->get($id);
        $this->assertSame('SUP-UPDATED', $updated->wholesaler_order_number);
        $this->assertSame((float)$before->total_ordered_amount, (float)$updated->total_ordered_amount);
        $this->assertSame(ReplenishmentStatus::Submitted, $updated->status);
    }

    public function testEditRejectsReceivedReplenishment(): void
    {
        $id = 'f6d1f429-877b-4d92-83a0-cb305d853da7';

        $this->get("/replenishments/edit/{$id}");

        $this->assertRedirect(['controller' => 'Replenishments', 'action' => 'view', $id]);
        $this->assertFlashMessage('Received replenishments cannot be edited.');
    }

    public function testAddPrepopulatesRequiredReplenishmentLines(): void
    {
        $badges = $this->getTableLocator()->get('Badges');
        $orders = $this->getTableLocator()->get('Orders');
        $orderLines = $this->getTableLocator()->get('OrderLines');
        $badgeId = 'f525eb6d-021c-4ef2-811f-feac8db8d35d';

        $badges->updateAll([
            'on_hand_quantity' => 2,
            'pending_quantity' => 3,
            'reserve_quantity' => 4,
        ], ['id' => $badgeId]);
        $orders->updateAll(
            ['status' => OrderStatus::Placed->value],
            ['id' => 'dd7b14cc-abe6-4e58-b63d-070678d78644'],
        );
        $orderLines->updateAll([
            'quantity' => 10,
            'fulfilled_quantity' => 2,
        ], ['id' => 'be20de8c-eea8-4114-a98e-1d55e483e8db']);

        $this->get('/replenishments/add');

        $this->assertResponseOk();
        $this->assertResponseContains('replenishment_order_lines[0][badge_id]');
        $this->assertResponseContains('replenishment_order_lines[0][quantity]');
        $this->assertResponseContains('value="7"');
        $this->assertResponseNotContains('replenishment_order_lines[1][badge_id]');
    }

    public function testAddRequiresAtLeastOneOrderLine(): void
    {
        $this->enableCsrfToken();
        $this->post('/replenishments/add', []);

        $this->assertResponseOk();
        $this->assertResponseContains('Add at least one replenishment order line.');
    }

    public function testLineRowReturnsGridRow(): void
    {
        $this->enableCsrfToken();
        $this->configRequest(['headers' => ['Accept' => 'application/json']]);
        $this->post('/replenishments/line-row', [
            'badge_id' => 'f525eb6d-021c-4ef2-811f-feac8db8d35d',
            'quantity' => 3,
            'unit_price' => '7.25',
            'monetary_amount' => '999.99',
            'index' => 4,
        ]);

        $this->assertResponseOk();
        $payload = json_decode((string)$this->_response->getBody(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertStringContainsString('Lorem ipsum dolor sit amet', $payload['html']);
        $this->assertStringContainsString('replenishment_order_lines[4][badge_id]', $payload['html']);
        $this->assertStringContainsString('replenishment_order_lines[4][quantity]', $payload['html']);
        $this->assertStringContainsString('replenishment_order_lines[4][unit_price]', $payload['html']);
        $this->assertStringContainsString('replenishment_order_lines[4][monetary_amount]', $payload['html']);
        $this->assertStringContainsString('value="21.75"', $payload['html']);
    }

    public function testLineRowRejectsInvalidLineAmount(): void
    {
        $this->enableCsrfToken();
        $this->configRequest(['headers' => ['Accept' => 'application/json']]);
        $this->post('/replenishments/line-row', [
            'badge_id' => 'f525eb6d-021c-4ef2-811f-feac8db8d35d',
            'quantity' => 3,
            'unit_price' => 'invalid',
            'index' => 0,
        ]);

        $this->assertResponseCode(422);
        $this->assertResponseContains('valid quantity and unit price');
    }

    public function testBadgePriceReturnsReplenishmentPrice(): void
    {
        $this->get(
            '/replenishments/badge-price?badge_id=f525eb6d-021c-4ef2-811f-feac8db8d35d',
        );

        $this->assertResponseOk();
        $payload = json_decode((string)$this->_response->getBody(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame('1.25', $payload['unit_price']);
    }

    public function testReceiveShowsExpectedQuantitySeparately(): void
    {
        $id = 'f6d1f429-877b-4d92-83a0-cb305d853da7';
        $this->makeReplenishmentReceivable($id);

        $this->get("/replenishments/receive/{$id}");

        $this->assertResponseOk();
        $this->assertResponseContains('Receive Replenishment');
        $this->assertResponseContains('Expected');
        $this->assertResponseContains('Received');
        $this->assertResponseContains('placeholder="0"');
        $this->assertResponseNotContains('Edit Replenishment');
    }

    public function testReceiveIsUnavailableForReceivedOrCancelledReplenishments(): void
    {
        $id = 'f6d1f429-877b-4d92-83a0-cb305d853da7';
        $replenishments = $this->getTableLocator()->get('Replenishments');

        foreach ([ReplenishmentStatus::Received, ReplenishmentStatus::Cancelled] as $status) {
            $replenishments->updateAll(['status' => $status->value], ['id' => $id]);

            $this->get('/replenishments');
            $this->assertResponseOk();
            $this->assertResponseNotContains('>Receive<');

            $this->get("/replenishments/view/{$id}");
            $this->assertResponseOk();
            $this->assertResponseNotContains('Receive Replenishment');

            $this->get("/replenishments/receive/{$id}");
            $this->assertRedirect(['controller' => 'Replenishments', 'action' => 'view', $id]);
            $this->assertFlashMessage(
                'Received or cancelled replenishments cannot receive more items.',
            );
        }
    }

    public function testReceiveCreatesReceiptLine(): void
    {
        $replenishments = $this->getTableLocator()->get('Replenishments');
        $id = 'f6d1f429-877b-4d92-83a0-cb305d853da7';
        $this->makeReplenishmentReceivable($id);
        $orderLine = $replenishments->ReplenishmentOrderLines->find()
            ->where(['replenishment_id' => $id])
            ->firstOrFail();
        $before = $replenishments->ReplenishmentReceiptLines->find()
            ->where(['replenishment_id' => $id])
            ->count();

        $this->enableCsrfToken();
        $this->post("/replenishments/receive/{$id}", [
            'receipt_lines' => [
                $orderLine->id => ['quantity' => 2],
            ],
        ]);

        $this->assertRedirect(['controller' => 'Replenishments', 'action' => 'view', $id]);
        $this->assertFlashMessage('The replenishment receipt has been recorded.');
        $this->assertSame(
            $before + 1,
            $replenishments->ReplenishmentReceiptLines->find()
                ->where(['replenishment_id' => $id])
                ->count(),
        );

        $receiptLine = $replenishments->ReplenishmentReceiptLines->find()
            ->where(['replenishment_id' => $id])
            ->orderByDesc('transaction_timestamp')
            ->firstOrFail();
        $this->assertSame($orderLine->badge_id, $receiptLine->badge_id);
        $this->assertSame(2, $receiptLine->receipted_quantity_change);
        $this->assertSame(2, $receiptLine->on_hand_quantity_change);
        $this->assertSame(-2, $receiptLine->pending_quantity_change);
        $this->assertSame((float)$orderLine->unit_price, (float)$receiptLine->unit_price);
        $this->assertSame(
            2 * (float)$orderLine->unit_price,
            (float)$receiptLine->monetary_amount,
        );
    }

    public function testReceiveUpdatesReplenishmentToPartiallyReceived(): void
    {
        $replenishments = $this->getTableLocator()->get('Replenishments');
        $id = 'f6d1f429-877b-4d92-83a0-cb305d853da7';
        $orderLine = $replenishments->ReplenishmentOrderLines->find()
            ->where(['replenishment_id' => $id])
            ->firstOrFail();
        $replenishments->updateAll([
            'status' => ReplenishmentStatus::Submitted,
            'order_submitted' => true,
            'received' => false,
            'received_date' => null,
            'total_ordered_quantity' => 4,
            'total_received_quantity' => 0,
        ], ['id' => $id]);

        $this->enableCsrfToken();
        $this->post("/replenishments/receive/{$id}", [
            'receipt_lines' => [
                $orderLine->id => ['quantity' => 2],
            ],
        ]);

        $this->assertRedirect(['controller' => 'Replenishments', 'action' => 'view', $id]);
        $updated = $replenishments->get($id);
        $this->assertSame(ReplenishmentStatus::PartiallyReceived, $updated->status);
        $this->assertFalse($updated->received);
        $this->assertNull($updated->received_date);
    }

    public function testReceiveUpdatesReplenishmentToReceived(): void
    {
        $replenishments = $this->getTableLocator()->get('Replenishments');
        $id = 'f6d1f429-877b-4d92-83a0-cb305d853da7';
        $orderLine = $replenishments->ReplenishmentOrderLines->find()
            ->where(['replenishment_id' => $id])
            ->firstOrFail();
        $replenishments->updateAll([
            'status' => ReplenishmentStatus::Submitted,
            'order_submitted' => true,
            'received' => false,
            'received_date' => null,
            'total_ordered_quantity' => 2,
            'total_received_quantity' => 0,
        ], ['id' => $id]);

        $this->enableCsrfToken();
        $this->post("/replenishments/receive/{$id}", [
            'receipt_lines' => [
                $orderLine->id => ['quantity' => 3],
            ],
        ]);

        $this->assertRedirect(['controller' => 'Replenishments', 'action' => 'view', $id]);
        $updated = $replenishments->get($id);
        $this->assertSame(ReplenishmentStatus::Received, $updated->status);
        $this->assertTrue($updated->received);
        $this->assertNotNull($updated->received_date);
    }

    public function testReceiveTreatsBlankQuantityAsZero(): void
    {
        $replenishments = $this->getTableLocator()->get('Replenishments');
        $id = 'f6d1f429-877b-4d92-83a0-cb305d853da7';
        $this->makeReplenishmentReceivable($id);
        $orderLine = $replenishments->ReplenishmentOrderLines->find()
            ->where(['replenishment_id' => $id])
            ->firstOrFail();
        $before = $replenishments->ReplenishmentReceiptLines->find()
            ->where(['replenishment_id' => $id])
            ->count();

        $this->enableCsrfToken();
        $this->post("/replenishments/receive/{$id}", [
            'receipt_lines' => [
                $orderLine->id => ['quantity' => ''],
            ],
        ]);

        $this->assertRedirect(['controller' => 'Replenishments', 'action' => 'view', $id]);
        $this->assertSame(
            $before,
            $replenishments->ReplenishmentReceiptLines->find()
                ->where(['replenishment_id' => $id])
                ->count(),
        );
        $this->assertSame(
            ReplenishmentStatus::Submitted,
            $replenishments->get($id)->status,
        );
    }

    public function testReceiveAllowsQuantityAboveExpected(): void
    {
        $replenishments = $this->getTableLocator()->get('Replenishments');
        $id = 'f6d1f429-877b-4d92-83a0-cb305d853da7';
        $this->makeReplenishmentReceivable($id);
        $orderLine = $replenishments->ReplenishmentOrderLines->find()
            ->where(['replenishment_id' => $id])
            ->firstOrFail();
        $quantity = $orderLine->pending_quantity_change + 3;

        $this->enableCsrfToken();
        $this->post("/replenishments/receive/{$id}", [
            'receipt_lines' => [
                $orderLine->id => ['quantity' => $quantity],
            ],
        ]);

        $this->assertRedirect(['controller' => 'Replenishments', 'action' => 'view', $id]);
        $receiptLine = $replenishments->ReplenishmentReceiptLines->find()
            ->where(['replenishment_id' => $id])
            ->orderByDesc('transaction_timestamp')
            ->firstOrFail();
        $this->assertSame($quantity, $receiptLine->receipted_quantity_change);
        $this->assertSame($quantity, $receiptLine->on_hand_quantity_change);
        $this->assertSame(
            -$orderLine->pending_quantity_change,
            $receiptLine->pending_quantity_change,
        );
    }

    public function testReceiveShowsRemainingQuantityAfterPartialReceipt(): void
    {
        $replenishments = $this->getTableLocator()->get('Replenishments');
        $id = 'f6d1f429-877b-4d92-83a0-cb305d853da7';
        $this->makeReplenishmentReceivable($id);
        $orderLine = $replenishments->ReplenishmentOrderLines->find()
            ->where(['replenishment_id' => $id])
            ->firstOrFail();
        $replenishments->ReplenishmentReceiptLines->saveOrFail(
            $replenishments->ReplenishmentReceiptLines->newEntity([
                'badge_id' => $orderLine->badge_id,
                'replenishment_id' => $id,
                'on_hand_quantity_change' => 1,
                'receipted_quantity_change' => 1,
                'pending_quantity_change' => -1,
                'fulfilled_quantity_change' => 0,
                'monetary_amount' => $orderLine->unit_price,
                'unit_price' => $orderLine->unit_price,
            ]),
        );

        $this->get("/replenishments/receive/{$id}");

        $this->assertResponseOk();
        $this->assertResponseContains(
            'data-expected-quantity="'
            . ($orderLine->pending_quantity_change - 1)
            . '"',
        );
    }

    /**
     * Test delete method
     *
     * @return void
     * @link \App\Controller\ReplenishmentsController::delete()
     */
    public function testDelete(): void
    {
        $replenishments = $this->getTableLocator()->get('Replenishments');
        $entity = $replenishments->newEntity([
            'created_date' => '2026-02-22 12:00:00',
            'order_submitted' => true,
            'order_submitted_date' => '2026-02-22 12:00:00',
            'received' => true,
            'received_date' => '2026-02-22 12:00:00',
            'total_ordered_amount' => 10.0,
            'total_ordered_quantity' => 3,
            'total_received_amount' => 8.0,
            'total_received_quantity' => 2,
            'replenishment_number' => 'WO-DELETE',
        ]);
        $replenishments->saveOrFail($entity);
        $id = $entity->id;
        $before = $replenishments->find()->count();

        $this->enableCsrfToken();
        $this->post("/replenishments/delete/{$id}");

        $this->assertRedirect(['controller' => 'Replenishments', 'action' => 'index']);
        $this->assertFlashMessage('The replenishment has been deleted.');
        $this->assertSame($before - 1, $replenishments->find()->count());
        $this->assertFalse($replenishments->exists(['id' => $id]));
    }

    public function testDeleteRejectsReceivedReplenishment(): void
    {
        $id = 'f6d1f429-877b-4d92-83a0-cb305d853da7';

        $this->get("/replenishments/view/{$id}");
        $this->assertResponseOk();
        $this->assertResponseNotContains('Delete Replenishment');

        $this->get('/replenishments');
        $this->assertResponseOk();
        $this->assertResponseNotContains('>Delete<');

        $this->enableCsrfToken();
        $this->post("/replenishments/delete/{$id}");

        $this->assertRedirect(['controller' => 'Replenishments', 'action' => 'index']);
        $this->assertFlashMessage('Received replenishments cannot be deleted.');
        $this->assertTrue($this->getTableLocator()->get('Replenishments')->exists(['id' => $id]));
    }

    private function makeReplenishmentReceivable(string $id): void
    {
        $this->getTableLocator()->get('Replenishments')->updateAll([
            'status' => ReplenishmentStatus::Submitted->value,
            'received' => false,
            'received_date' => null,
        ], ['id' => $id]);
    }
}
