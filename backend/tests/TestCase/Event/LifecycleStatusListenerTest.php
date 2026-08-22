<?php
declare(strict_types=1);

namespace App\Test\TestCase\Event;

use App\Event\LifecycleStatusListener;
use App\Model\Enum\FulfilmentStatus;
use App\Model\Enum\OrderStatus;
use App\Model\Enum\ReplenishmentStatus;
use App\Model\Enum\TransactionType;
use Cake\Event\Event;
use Cake\Event\EventManager;
use Cake\TestSuite\TestCase;

class LifecycleStatusListenerTest extends TestCase
{
    /**
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Groups',
        'app.Accounts',
        'app.Users',
        'app.Audits',
        'app.Badges',
        'app.Orders',
        'app.OrderLines',
        'app.Fulfilments',
        'app.Replenishments',
        'app.StockTransactions',
    ];

    public function testOrderPlacedAdvancesDraftOrder(): void
    {
        $orders = $this->getTableLocator()->get('Orders');
        $order = $orders->get('dd7b14cc-abe6-4e58-b63d-070678d78644');
        $orders->updateAll([
            'status' => OrderStatus::Draft,
            'fulfilled' => false,
        ], ['id' => $order->id]);
        $order->set('status', OrderStatus::Draft);

        $this->dispatch(new Event('Order.afterPlace', $order));

        $updated = $orders->get($order->id);
        $this->assertSame(OrderStatus::Placed, $updated->status);
        $this->assertFalse($updated->fulfilled);
    }

    public function testFulfilmentDispatchAdvancesLinkedOrder(): void
    {
        $orders = $this->getTableLocator()->get('Orders');
        $fulfilments = $this->getTableLocator()->get('Fulfilments');
        $stockTransactions = $this->getTableLocator()->get('StockTransactions');
        $order = $orders->get('dd7b14cc-abe6-4e58-b63d-070678d78644');
        $orders->updateAll([
            'status' => OrderStatus::Placed,
            'fulfilled' => false,
            'total_ordered_quantity' => 4,
            'total_fulfilled_quantity' => 0,
        ], ['id' => $order->id]);
        $orders->OrderLines->updateAll([
            'quantity' => 4,
            'fulfilled_quantity' => 0,
            'fulfilled' => false,
        ], ['id' => 'be20de8c-eea8-4114-a98e-1d55e483e8db']);
        $stockTransactions->updateAll([
            'order_line_id' => 'be20de8c-eea8-4114-a98e-1d55e483e8db',
            'transaction_type' => TransactionType::Fulfilment->value,
            'fulfilled_quantity_change' => 2,
        ], [
            'id' => 'bad57a31-305f-4398-87d6-8fcfe4600793',
        ]);
        $fulfilments->updateAll([
            'status' => FulfilmentStatus::Draft,
            'dispatched_date' => null,
        ], ['id' => 'be5a0a9f-9d87-4191-b819-b7e1c1c50a3a']);

        $this->dispatch(new Event(
            'Fulfilment.afterDispatch',
            $fulfilments->get('be5a0a9f-9d87-4191-b819-b7e1c1c50a3a'),
        ));

        $updated = $orders->get($order->id);
        $this->assertSame(OrderStatus::PartiallyFulfilled, $updated->status);
        $this->assertFalse($updated->fulfilled);

        $stockTransactions->updateAll([
            'fulfilled_quantity_change' => 4,
        ], [
            'id' => 'bad57a31-305f-4398-87d6-8fcfe4600793',
        ]);
        $fulfilments->updateAll([
            'status' => FulfilmentStatus::Draft,
            'dispatched_date' => null,
        ], ['id' => 'be5a0a9f-9d87-4191-b819-b7e1c1c50a3a']);
        $this->dispatch(new Event(
            'Fulfilment.afterDispatch',
            $fulfilments->get('be5a0a9f-9d87-4191-b819-b7e1c1c50a3a'),
        ));

        $updated = $orders->get($order->id);
        $this->assertSame(OrderStatus::Fulfilled, $updated->status);
        $this->assertTrue($updated->fulfilled);
    }

    public function testReplenishmentSubmittedAdvancesDraftReplenishment(): void
    {
        $replenishments = $this->getTableLocator()->get('Replenishments');
        $replenishment = $replenishments->get('f6d1f429-877b-4d92-83a0-cb305d853da7');
        $replenishments->updateAll([
            'status' => ReplenishmentStatus::Draft,
            'order_submitted' => false,
            'order_submitted_date' => null,
            'received' => false,
            'received_date' => null,
        ], ['id' => $replenishment->id]);
        $replenishment = $replenishments->get($replenishment->id);

        $this->dispatch(new Event('Replenishment.afterSubmit', $replenishment));

        $updated = $replenishments->get($replenishment->id);
        $this->assertSame(ReplenishmentStatus::Submitted, $updated->status);
        $this->assertTrue($updated->order_submitted);
        $this->assertNotNull($updated->order_submitted_date);
        $this->assertFalse($updated->received);
    }

    public function testReplenishmentReceiptAdvancesStatusFromTotals(): void
    {
        $replenishments = $this->getTableLocator()->get('Replenishments');
        $stockTransactions = $this->getTableLocator()->get('StockTransactions');
        $replenishment = $replenishments->get('f6d1f429-877b-4d92-83a0-cb305d853da7');
        $replenishments->updateAll([
            'status' => ReplenishmentStatus::Submitted,
            'order_submitted' => true,
            'received' => false,
            'received_date' => null,
            'total_ordered_quantity' => 4,
            'total_received_quantity' => 2,
        ], ['id' => $replenishment->id]);
        $stockTransactions->updateAll([
            'receipted_quantity_change' => 2,
            'pending_quantity_change' => -2,
        ], ['id' => '9b86a2d1-6f94-4d6b-a6b2-0f68f2f30c12']);

        $this->dispatch(new Event(
            'Replenishment.afterReceive',
            $replenishments->get($replenishment->id),
        ));

        $updated = $replenishments->get($replenishment->id);
        $this->assertSame(ReplenishmentStatus::PartiallyReceived, $updated->status);
        $this->assertFalse($updated->received);

        $replenishments->updateAll([
            'total_received_quantity' => 4,
        ], ['id' => $replenishment->id]);
        $stockTransactions->updateAll([
            'receipted_quantity_change' => 4,
            'pending_quantity_change' => -3,
        ], ['id' => '9b86a2d1-6f94-4d6b-a6b2-0f68f2f30c12']);
        $this->dispatch(new Event(
            'Replenishment.afterReceive',
            $replenishments->get($replenishment->id),
        ));

        $updated = $replenishments->get($replenishment->id);
        $this->assertSame(ReplenishmentStatus::Received, $updated->status);
        $this->assertTrue($updated->received);
        $this->assertNotNull($updated->received_date);
    }

    public function testReplenishmentOverreceiptDoesNotCompleteAnotherLine(): void
    {
        $replenishments = $this->getTableLocator()->get('Replenishments');
        $stockTransactions = $this->getTableLocator()->get('StockTransactions');
        $id = 'f6d1f429-877b-4d92-83a0-cb305d853da7';
        $replenishments->updateAll([
            'status' => ReplenishmentStatus::Submitted,
            'order_submitted' => true,
            'received' => false,
            'received_date' => null,
            'total_ordered_quantity' => 4,
            'total_received_quantity' => 4,
        ], ['id' => $id]);
        $stockTransactions->updateAll([
            'replenishment_id' => $id,
            'transaction_type' => TransactionType::ReplenishmentOrder->value,
            'receipted_quantity_change' => 0,
            'pending_quantity_change' => 2,
        ], ['id' => 'bad57a31-305f-4398-87d6-8fcfe4600793']);
        $stockTransactions->updateAll([
            'receipted_quantity_change' => 4,
            'pending_quantity_change' => -2,
        ], ['id' => '9b86a2d1-6f94-4d6b-a6b2-0f68f2f30c12']);

        $this->dispatch(new Event(
            'Replenishment.afterReceive',
            $replenishments->get($id),
        ));

        $updated = $replenishments->get($id);
        $this->assertSame(ReplenishmentStatus::PartiallyReceived, $updated->status);
        $this->assertFalse($updated->received);
        $this->assertNull($updated->received_date);
    }

    public function testFulfilmentDispatchAdvancesDraftFulfilment(): void
    {
        $fulfilments = $this->getTableLocator()->get('Fulfilments');
        $fulfilment = $fulfilments->get('be5a0a9f-9d87-4191-b819-b7e1c1c50a3a');
        $fulfilments->updateAll([
            'status' => FulfilmentStatus::Draft,
            'dispatched_date' => null,
        ], ['id' => $fulfilment->id]);
        $fulfilment->set('status', FulfilmentStatus::Draft);
        $fulfilment->set('dispatched_date', null);

        $this->dispatch(new Event('Fulfilment.afterDispatch', $fulfilment));

        $updated = $fulfilments->get($fulfilment->id);
        $this->assertSame(FulfilmentStatus::Dispatched, $updated->status);
        $this->assertNotNull($updated->dispatched_date);

        $dispatchedDate = $updated->dispatched_date;
        $this->dispatch(new Event('Fulfilment.afterDispatch', $updated));
        $updated = $fulfilments->get($fulfilment->id);
        $this->assertEquals($dispatchedDate, $updated->dispatched_date);
    }

    private function dispatch(Event $event): void
    {
        $events = new EventManager();
        $events->on(new LifecycleStatusListener());
        $events->dispatch($event);
    }
}
