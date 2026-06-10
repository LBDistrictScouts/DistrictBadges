<?php
declare(strict_types=1);

namespace App\Test\TestCase\View\Helper;

use App\Model\Entity\Fulfilment;
use App\Model\Entity\FulfilmentLine;
use App\View\AppView;
use Cake\TestSuite\TestCase;

class StockTransactionLinesHelperTest extends TestCase
{
    public function testGridUsesConfiguredInputKeyAndFields(): void
    {
        $view = new AppView();
        $view->initialize();
        $line = new FulfilmentLine([
            'badge_id' => 'badge-id',
            'order_line_id' => 'order-line-id',
            'quantity' => 3,
            'unit_price' => '2.50',
            'monetary_amount' => '7.50',
        ]);
        $entity = new Fulfilment(['fulfilment_lines' => [$line]]);

        $html = $view->StockTransactionLines->grid($entity, [
            'badge-id' => 'Badge name',
        ], [
            'inputKey' => 'fulfilment_lines',
            'property' => 'fulfilment_lines',
            'legend' => 'Lines',
            'rowUrl' => '/fulfilments/line-row',
            'priceUrl' => '/fulfilments/badge-price',
            'selectors' => [
                'order_line_id' => [
                    'label' => 'Order Line',
                    'empty' => 'Select an order line',
                    'options' => [
                        'order-line-id' => 'ORD-1 - Badge name',
                    ],
                ],
            ],
            'fields' => [
                'quantity' => [
                    'label' => 'Quantity',
                    'min' => 1,
                    'default' => 1,
                    'editable' => true,
                ],
                'unit_price' => [
                    'label' => 'Unit Price',
                    'type' => 'decimal',
                    'step' => '0.01',
                    'editable' => true,
                    'currency' => 'GBP',
                ],
                'monetary_amount' => [
                    'label' => 'Line Amount',
                    'type' => 'decimal',
                    'step' => '0.01',
                    'currency' => 'GBP',
                    'calculation' => [
                        'operation' => 'multiply',
                        'fields' => ['quantity', 'unit_price'],
                    ],
                ],
            ],
        ]);

        $this->assertStringContainsString('fulfilment_lines[0][badge_id]', $html);
        $this->assertStringContainsString('fulfilment_lines[0][order_line_id]', $html);
        $this->assertStringContainsString('data-stock-line-selector="order_line_id"', $html);
        $this->assertStringContainsString('ORD-1 - Badge name', $html);
        $this->assertStringContainsString('fulfilment_lines[0][quantity]', $html);
        $this->assertStringContainsString('data-stock-row-field="quantity"', $html);
        $this->assertStringContainsString('fulfilment_lines[0][unit_price]', $html);
        $this->assertStringContainsString('fulfilment_lines[0][monetary_amount]', $html);
        $this->assertStringContainsString('data-stock-row-field="unit_price"', $html);
        $this->assertStringContainsString(
            'data-stock-line-output="monetary_amount"',
            $html,
        );
        $this->assertStringContainsString(
            'data-stock-row-output="monetary_amount"',
            $html,
        );
        $this->assertStringContainsString('stock-line-currency-control', $html);
        $this->assertStringContainsString('£7.50', $html);
        $this->assertStringContainsString('currency: currency', $html);
        $this->assertStringNotContainsString(
            'readonly="readonly" data-stock-line-field="monetary_amount"',
            $html,
        );
        $this->assertStringContainsString('calculation.operation', $html);
        $this->assertStringContainsString('\/fulfilments\/badge-price', $html);
        $this->assertStringContainsString('badgeSelect.select2({', $html);
        $this->assertStringContainsString("badgeSelect.on('change'", $html);
        $this->assertStringContainsString("trigger('change.select2')", $html);
        $this->assertStringContainsString('var resetFields = function ()', $html);
        $this->assertStringContainsString('var selectorNames = ["order_line_id"]', $html);
        $this->assertStringContainsString('resetFields();', $html);
        $this->assertStringContainsString('Badge name', $html);
        $this->assertStringContainsString('data-stock-transaction-lines', $html);
        $this->assertStringContainsString(
            'class="row stock-line-builder"',
            $html,
        );
        $this->assertStringContainsString(
            'class="column stock-line-badge-column"',
            $html,
        );
        $this->assertSame(
            4,
            substr_count($html, 'class="column stock-line-builder-column"'),
        );
    }

    public function testGridSupportsBulkLoaderWithoutManualLineBuilder(): void
    {
        $view = new AppView();
        $view->initialize();

        $html = $view->StockTransactionLines->grid(
            new Fulfilment(),
            [],
            [
                'inputKey' => 'fulfilment_lines',
                'legend' => 'Lines',
                'rowUrl' => '/fulfilments/line-row',
                'hideLineBuilder' => true,
                'bulkLoader' => [
                    'field' => 'order_id',
                    'label' => 'Order',
                    'empty' => 'Select an order',
                    'options' => ['order-id' => 'ORD-1'],
                    'url' => '/fulfilments/order-lines',
                ],
                'fields' => [],
            ],
        );

        $this->assertStringContainsString('data-stock-line-bulk-source', $html);
        $this->assertStringContainsString('data-stock-line-bulk-add', $html);
        $this->assertStringContainsString('Select an order', $html);
        $this->assertStringContainsString('\/fulfilments\/order-lines', $html);
        $this->assertStringContainsString(
            "bulkAddButton.addEventListener('click'",
            $html,
        );
        $this->assertStringContainsString(
            "grid.insertAdjacentHTML('beforeend', payload.html)",
            $html,
        );
        $this->assertStringContainsString('existing_badge_quantities[', $html);
        $this->assertStringNotContainsString(
            '<button type="button" class="button button-outline" data-stock-line-add>',
            $html,
        );
        $this->assertStringNotContainsString(
            '<select name="line_badge_id"',
            $html,
        );
    }
}
