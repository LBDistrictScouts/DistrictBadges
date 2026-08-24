<?php
declare(strict_types=1);

namespace App\Test\TestCase\Queue\Processor;

use App\Model\Enum\OrderStatus;
use App\Queue\Processor\OrderProcessor;
use Cake\TestSuite\TestCase;

class OrderProcessorTest extends TestCase
{
    protected array $fixtures = [
        'app.Groups',
        'app.Sections',
        'app.Accounts',
        'app.Users',
        'app.Badges',
        'app.Orders',
        'app.OrderLines',
    ];

    public function testProcessUsesApiPayloadContractAndCreatesOrder(): void
    {
        $processor = new OrderProcessor();
        $processor->setTableLocator($this->getTableLocator());

        $result = $processor->process(json_encode($this->validPayload(), JSON_THROW_ON_ERROR));

        $this->assertSame(OrderProcessor::ACK, $result);
        $order = $this->getTableLocator()->get('Orders')->find()
            ->where(['section_id' => 'd9534dcb-a846-5a22-a2fe-b67580555563'])
            ->orderByDesc('placed_date')
            ->contain(['OrderLines'])
            ->firstOrFail();
        $this->assertSame('ae471706-04cc-4c9c-8916-e4be1f913edf', $order->account_id);
        $this->assertSame(OrderStatus::Placed, $order->status);
        $this->assertSame(2, $order->total_ordered_quantity);
        $this->assertEquals(3.0, $order->total_ordered_amount);
        $this->assertSame('1.50', $order->order_lines[0]->unit_price);
        $this->assertTrue($this->getTableLocator()->get('Users')->exists(['email' => 'queue@example.org']));
    }

    public function testProcessRejectsPayloadRejectedByApiValidation(): void
    {
        $processor = new OrderProcessor();
        $processor->setTableLocator($this->getTableLocator());
        $payload = $this->validPayload();
        $payload['email'] = 'invalid';

        $this->assertSame(
            OrderProcessor::REJECT,
            $processor->process(json_encode($payload, JSON_THROW_ON_ERROR)),
        );
    }

    public function testProcessRejectsInvalidJson(): void
    {
        $this->assertSame(OrderProcessor::REJECT, (new OrderProcessor())->process('{'));
    }

    /** @return array<string, mixed> */
    private function validPayload(): array
    {
        return [
            'first_name' => 'Queue',
            'last_name' => 'Leader',
            'email' => 'queue@example.org',
            'group_id' => '4d5149f3-6214-4457-a04d-e428dc1200d7',
            'section_id' => 'd9534dcb-a846-5a22-a2fe-b67580555563',
            'lines' => [[
                'badge_id' => 'f525eb6d-021c-4ef2-811f-feac8db8d35d',
                'quantity' => 2,
            ]],
        ];
    }
}
