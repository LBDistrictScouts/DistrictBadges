<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Component;

use App\Controller\Component\StockTransactionLinesComponent;
use App\Model\Entity\Fulfilment;
use Cake\Controller\ComponentRegistry;
use Cake\Controller\Controller;
use Cake\Http\ServerRequest;
use Cake\ORM\Table;
use Cake\TestSuite\TestCase;

class StockTransactionLinesComponentTest extends TestCase
{
    private StockTransactionLinesComponent $component;

    protected function setUp(): void
    {
        parent::setUp();
        $this->component = new StockTransactionLinesComponent(
            new ComponentRegistry(new Controller(new ServerRequest())),
        );
    }

    public function testNormaliseSupportsDifferentStockMappings(): void
    {
        $data = $this->component->normalise([
            'replenishment_order_lines' => [
                [
                    'badge_id' => 'badge-id',
                    'quantity' => 5,
                ],
            ],
        ], 'replenishment-id', [
            'inputKey' => 'replenishment_order_lines',
            'foreignKey' => 'replenishment_id',
            'fields' => [
                'quantity' => [
                    'min' => 1,
                    'changes' => [
                        'pending_quantity_change' => 1,
                    ],
                ],
            ],
        ]);

        $this->assertSame([
            'badge_id' => 'badge-id',
            'replenishment_id' => 'replenishment-id',
            'on_hand_quantity_change' => 0,
            'receipted_quantity_change' => 0,
            'pending_quantity_change' => 5,
            'fulfilled_quantity_change' => 0,
        ], $data['replenishment_order_lines'][0]);
    }

    public function testNormaliseSupportsMultipleAuditFields(): void
    {
        $data = $this->component->normalise([
            'audit_lines' => [
                [
                    'badge_id' => 'badge-id',
                    'on_hand' => 7,
                    'receipted' => 2,
                ],
            ],
        ], 'audit-id', [
            'inputKey' => 'audit_lines',
            'foreignKey' => 'audit_id',
            'fields' => [
                'on_hand' => [
                    'min' => 0,
                    'changes' => ['on_hand_quantity_change' => 1],
                ],
                'receipted' => [
                    'min' => 0,
                    'changes' => ['receipted_quantity_change' => 1],
                ],
            ],
        ]);

        $this->assertSame(7, $data['audit_lines'][0]['on_hand_quantity_change']);
        $this->assertSame(2, $data['audit_lines'][0]['receipted_quantity_change']);
        $this->assertSame(0, $data['audit_lines'][0]['pending_quantity_change']);
    }

    public function testNormaliseSupportsDecimalTargetFields(): void
    {
        $data = $this->component->normalise([
            'replenishment_order_lines' => [
                [
                    'badge_id' => 'badge-id',
                    'quantity' => 2,
                    'unit_price' => '4.5',
                    'monetary_amount' => '999.99',
                ],
            ],
        ], 'replenishment-id', [
            'inputKey' => 'replenishment_order_lines',
            'foreignKey' => 'replenishment_id',
            'fields' => [
                'quantity' => [
                    'min' => 1,
                    'changes' => ['pending_quantity_change' => 1],
                ],
                'unit_price' => [
                    'type' => 'decimal',
                    'min' => 0,
                    'target' => 'unit_price',
                ],
                'monetary_amount' => [
                    'type' => 'decimal',
                    'min' => 0,
                    'target' => 'monetary_amount',
                    'calculation' => [
                        'operation' => 'multiply',
                        'fields' => ['quantity', 'unit_price'],
                    ],
                ],
            ],
        ]);

        $this->assertSame(2, $data['replenishment_order_lines'][0]['pending_quantity_change']);
        $this->assertSame('4.50', $data['replenishment_order_lines'][0]['unit_price']);
        $this->assertSame('9.00', $data['replenishment_order_lines'][0]['monetary_amount']);
    }

    public function testNormaliseConsolidatesRepeatedBadgesForOrdersFulfilmentsAndReplenishments(): void
    {
        $order = $this->component->normalise([
            'order_lines' => [
                ['badge_id' => 'badge-id', 'quantity' => 10, 'unit_price' => '1.50'],
                ['badge_id' => 'badge-id', 'quantity' => 5, 'unit_price' => '1.50'],
            ],
        ], 'order-id', [
            'inputKey' => 'order_lines',
            'foreignKey' => 'order_id',
            'stockTransaction' => false,
            'fields' => [
                'quantity' => ['min' => 1, 'target' => 'quantity'],
                'unit_price' => ['type' => 'decimal', 'min' => 0, 'target' => 'unit_price'],
                'amount' => [
                    'type' => 'decimal',
                    'min' => 0,
                    'target' => 'amount',
                    'calculation' => ['operation' => 'multiply', 'fields' => ['quantity', 'unit_price']],
                ],
            ],
            'deduplicate' => [
                'sum' => ['quantity' => 'quantity', 'amount' => 'money'],
                'weightedPrice' => ['field' => 'unit_price', 'quantity' => 'quantity', 'amount' => 'amount'],
            ],
        ]);
        $this->assertCount(1, $order['order_lines']);
        $this->assertSame(15, $order['order_lines'][0]['quantity']);
        $this->assertSame('22.50', $order['order_lines'][0]['amount']);

        $fulfilment = $this->component->normalise([
            'fulfilment_lines' => [
                ['badge_id' => 'badge-id', 'order_line_id' => 'order-line-a', 'quantity' => 10, 'unit_price' => '1.50'],
                ['badge_id' => 'badge-id', 'order_line_id' => 'order-line-a', 'quantity' => 5, 'unit_price' => '1.50'],
                ['badge_id' => 'badge-id', 'order_line_id' => 'order-line-b', 'quantity' => 2, 'unit_price' => '1.50'],
            ],
        ], 'fulfilment-id', [
            'inputKey' => 'fulfilment_lines',
            'foreignKey' => 'fulfilment_id',
            'selectors' => ['order_line_id' => []],
            'fields' => [
                'quantity' => [
                    'min' => 1,
                    'changes' => [
                        'on_hand_quantity_change' => -1,
                        'fulfilled_quantity_change' => 1,
                    ],
                ],
                'unit_price' => ['type' => 'decimal', 'min' => 0, 'target' => 'unit_price'],
                'monetary_amount' => [
                    'type' => 'decimal',
                    'min' => 0,
                    'target' => 'monetary_amount',
                    'calculation' => ['operation' => 'multiply', 'fields' => ['quantity', 'unit_price']],
                ],
            ],
            'deduplicate' => [
                'sum' => [
                    'on_hand_quantity_change' => 'quantity',
                    'fulfilled_quantity_change' => 'quantity',
                    'monetary_amount' => 'money',
                ],
                'weightedPrice' => [
                    'field' => 'unit_price',
                    'quantity' => 'fulfilled_quantity_change',
                    'amount' => 'monetary_amount',
                ],
            ],
        ]);
        $this->assertCount(2, $fulfilment['fulfilment_lines']);
        $this->assertSame(15, $fulfilment['fulfilment_lines'][0]['fulfilled_quantity_change']);
        $this->assertSame('22.50', $fulfilment['fulfilment_lines'][0]['monetary_amount']);
        $this->assertSame('order-line-b', $fulfilment['fulfilment_lines'][1]['order_line_id']);

        $replenishment = $this->component->normalise([
            'replenishment_order_lines' => [
                ['badge_id' => 'badge-id', 'quantity' => 10, 'unit_price' => '1.50'],
                ['badge_id' => 'badge-id', 'quantity' => 5, 'unit_price' => '1.50'],
            ],
        ], 'replenishment-id', [
            'inputKey' => 'replenishment_order_lines',
            'foreignKey' => 'replenishment_id',
            'fields' => [
                'quantity' => ['min' => 1, 'changes' => ['pending_quantity_change' => 1]],
                'unit_price' => ['type' => 'decimal', 'min' => 0, 'target' => 'unit_price'],
                'monetary_amount' => [
                    'type' => 'decimal',
                    'min' => 0,
                    'target' => 'monetary_amount',
                    'calculation' => ['operation' => 'multiply', 'fields' => ['quantity', 'unit_price']],
                ],
            ],
            'deduplicate' => [
                'sum' => ['pending_quantity_change' => 'quantity', 'monetary_amount' => 'money'],
                'weightedPrice' => [
                    'field' => 'unit_price',
                    'quantity' => 'pending_quantity_change',
                    'amount' => 'monetary_amount',
                ],
            ],
        ]);
        $this->assertCount(1, $replenishment['replenishment_order_lines']);
        $this->assertSame(15, $replenishment['replenishment_order_lines'][0]['pending_quantity_change']);
        $this->assertSame('22.50', $replenishment['replenishment_order_lines'][0]['monetary_amount']);
    }

    public function testNormaliseCopiesConfiguredSelectors(): void
    {
        $data = $this->component->normalise([
            'fulfilment_lines' => [
                [
                    'badge_id' => 'badge-id',
                    'order_line_id' => 'order-line-id',
                    'quantity' => 2,
                ],
            ],
        ], 'fulfilment-id', [
            'inputKey' => 'fulfilment_lines',
            'foreignKey' => 'fulfilment_id',
            'selectors' => [
                'order_line_id' => [],
            ],
            'fields' => [
                'quantity' => [
                    'min' => 1,
                    'changes' => ['fulfilled_quantity_change' => 1],
                ],
            ],
        ]);

        $this->assertSame(
            'order-line-id',
            $data['fulfilment_lines'][0]['order_line_id'],
        );
    }

    public function testRequireLinesAddsConfiguredError(): void
    {
        $entity = new Fulfilment();
        $this->component->requireLines($entity, ['lines' => []], [
            'inputKey' => 'lines',
            'requiredMessage' => 'Add a line.',
        ]);

        $this->assertSame(['Add a line.'], $entity->getError('lines'));
    }

    public function testNormaliseRejectsStructuredIdentifiers(): void
    {
        $data = $this->component->normalise([
            'lines' => [[
                'badge_id' => ['invalid'],
                'order_line_id' => ['invalid'],
                'quantity' => 1,
            ]],
        ], 'parent-id', [
            'inputKey' => 'lines',
            'foreignKey' => 'parent_id',
            'selectors' => ['order_line_id' => []],
            'fields' => ['quantity' => ['target' => 'quantity']],
        ]);

        $this->assertNull($data['lines'][0]['badge_id']);
        $this->assertNull($data['lines'][0]['order_line_id']);
        $this->assertSame(1, $data['lines'][0]['quantity']);
    }

    public function testRowResponseRejectsStructuredBadgeIdentifierBeforeQuerying(): void
    {
        $request = new ServerRequest(['environment' => ['REQUEST_METHOD' => 'POST']]);
        $this->component->getController()->setRequest($request->withParsedBody([
            'badge_id' => ['invalid'],
            'index' => 0,
            'quantity' => 1,
        ]));

        $response = $this->component->rowResponse(new Table(), [
            'inputKey' => 'lines',
            'fields' => ['quantity' => ['label' => 'Quantity', 'min' => 1]],
        ]);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame(
            ['error' => 'Complete all line fields with valid values.'],
            json_decode((string)$response->getBody(), true, flags: JSON_THROW_ON_ERROR),
        );
    }
}
