<?php
declare(strict_types=1);

namespace App\Test\TestCase\Event;

use App\Event\OrderLineFulfilmentListener;
use Cake\Event\Event;
use Cake\Event\EventManager;
use Cake\TestSuite\TestCase;

class OrderLineFulfilmentListenerTest extends TestCase
{
    /**
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Groups',
        'app.Accounts',
        'app.Users',
        'app.Badges',
        'app.Orders',
        'app.OrderLines',
        'app.Fulfilments',
        'app.Replenishments',
        'app.FulfilmentLines',
    ];

    public function testPartialFulfilmentRecordsRemainingQuantity(): void
    {
        $orderLines = $this->getTableLocator()->get('OrderLines');
        $stockTransactions = $this->getTableLocator()->get('StockTransactions');
        $orderLineId = 'be20de8c-eea8-4114-a98e-1d55e483e8db';
        $orderLines->updateAll([
            'quantity' => 10,
            'fulfilled_quantity' => 0,
            'fulfilled' => false,
        ], ['id' => $orderLineId]);
        $stockTransactions->updateAll([
            'order_line_id' => $orderLineId,
            'fulfilment_id' => 'be5a0a9f-9d87-4191-b819-b7e1c1c50a3a',
            'fulfilled_quantity_change' => 4,
        ], ['id' => '2e3f4051-2222-4c3b-9d4e-1b2c3d4e5f60']);

        $this->dispatch($orderLines->get($orderLineId));

        $updated = $orderLines->get($orderLineId);
        $this->assertSame(4, $updated->fulfilled_quantity);
        $this->assertSame(6, $updated->remaining_quantity);
        $this->assertFalse($updated->fulfilled);
    }

    public function testLaterFulfilmentCompletesOrderLine(): void
    {
        $orderLines = $this->getTableLocator()->get('OrderLines');
        $stockTransactions = $this->getTableLocator()->get('StockTransactions');
        $orderLineId = 'be20de8c-eea8-4114-a98e-1d55e483e8db';
        $orderLines->updateAll([
            'quantity' => 10,
            'fulfilled_quantity' => 4,
            'fulfilled' => false,
        ], ['id' => $orderLineId]);
        $stockTransactions->updateAll([
            'order_line_id' => $orderLineId,
            'fulfilment_id' => 'be5a0a9f-9d87-4191-b819-b7e1c1c50a3a',
            'fulfilled_quantity_change' => 10,
        ], ['id' => '2e3f4051-2222-4c3b-9d4e-1b2c3d4e5f60']);

        $this->dispatch($orderLines->get($orderLineId));

        $updated = $orderLines->get($orderLineId);
        $this->assertSame(10, $updated->fulfilled_quantity);
        $this->assertSame(0, $updated->remaining_quantity);
        $this->assertTrue($updated->fulfilled);
    }

    private function dispatch(object $orderLine): void
    {
        $events = new EventManager();
        $events->on(new OrderLineFulfilmentListener());
        $events->dispatch(new Event('OrderLine.afterFulfilment', $orderLine));
    }
}
