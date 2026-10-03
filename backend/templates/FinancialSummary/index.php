<?php
/**
 * @var \App\View\AppView $this
 * @var int $year
 * @var array<int> $years
 * @var array<int, array{name: string, invoiced: float, stock_received: float, postage: float, invoice_count: int, net: float}> $months
 * @var array{invoiced: float, stock_received: float, postage: float, invoice_count: int, net: float} $totals
 * @var array{cost: float, sale_value: float, units_on_hand: int, valued_units: int, unpriced_units: int, unpriced_badges: int, fulfilled_not_invoiced: float, unfulfilled_value: float, as_of: string} $stockValue
 */
$this->assign('title', 'Financial summary');
echo $this->Html->css('financial-summary', ['block' => true]);

$monthlyScale = 1.0;
foreach ($months as $month) {
    $monthlyScale = max($monthlyScale, $month['invoiced'], $month['stock_received'] + $month['postage']);
}
?>

<section class="financial-summary">
    <header class="financial-heading">
        <div>
            <p class="financial-eyebrow">Finance</p>
            <h1>Financial summary</h1>
            <p>Invoiced sales and recorded replenishment costs, month by month.</p>
        </div>
        <?= $this->Form->create(null, ['type' => 'get', 'class' => 'financial-year-filter']) ?>
            <?= $this->Form->label('year', 'Year') ?>
            <?= $this->Form->select('year', array_combine($years, $years), [
                'value' => $year,
                'id' => 'financial-year',
                'class' => 'financial-year-filter__select',
                'onchange' => 'this.form.submit()',
            ]) ?>
            <button type="submit" class="financial-year-filter__button">View year</button>
        <?= $this->Form->end() ?>
    </header>

    <section class="financial-metrics" aria-label="<?= h($year) ?> annual totals">
        <article class="financial-metric financial-metric--income">
            <span>Invoiced</span>
            <strong><?= $this->Number->currency($totals['invoiced'], 'GBP') ?></strong>
            <small><?= $this->Number->format($totals['invoice_count']) ?> invoices</small>
        </article>
        <article class="financial-metric financial-metric--cost">
            <span>Stock received</span>
            <strong><?= $this->Number->currency($totals['stock_received'], 'GBP') ?></strong>
            <small>Value of goods received</small>
        </article>
        <article class="financial-metric financial-metric--postage">
            <span>Inbound postage</span>
            <strong><?= $this->Number->currency($totals['postage'], 'GBP') ?></strong>
            <small>Recorded actual costs</small>
        </article>
        <article class="financial-metric financial-metric--net <?= $totals['net'] < 0 ? 'is-negative' : '' ?>">
            <span>After recorded costs</span>
            <strong><?= $this->Number->currency($totals['net'], 'GBP') ?></strong>
            <small>Invoiced less stock and postage</small>
        </article>
    </section>

    <section class="financial-stock" aria-label="Stock value as of <?= h($stockValue['as_of']) ?>">
        <div class="financial-stock__copy">
            <p class="financial-eyebrow">Stock position as of <?= h($stockValue['as_of']) ?></p>
            <h2>Value of stock</h2>
            <p class="financial-stock__quantity"><?= $this->Number->format($stockValue['units_on_hand']) ?> units on hand</p>
            <p>Quantities come from stock transactions. Cost uses the latest receipt price by this date; sale value uses current catalogue prices.</p>
        </div>
        <div class="financial-stock__figures">
            <div class="financial-stock__figure financial-stock__figure--cost">
                <span>Cost</span>
                <strong><?= $this->Number->currency($stockValue['cost'], 'GBP') ?></strong>
                <small><?= $this->Number->format($stockValue['valued_units']) ?> units with a recorded receipt cost</small>
            </div>
            <div class="financial-stock__figure financial-stock__figure--sale">
                <span>Sale value</span>
                <strong><?= $this->Number->currency($stockValue['sale_value'], 'GBP') ?></strong>
                <small>At current catalogue prices</small>
            </div>
        </div>
        <?= $this->Html->link('View stock', ['controller' => 'Badges', 'action' => 'stock'], ['class' => 'financial-stock__link']) ?>
        <?php if ($stockValue['unpriced_units'] > 0) : ?>
            <p class="financial-stock__note"><?= $this->Number->format($stockValue['unpriced_units']) ?> units across <?= $this->Number->format($stockValue['unpriced_badges']) ?> badge types have no recorded receipt cost and are excluded from the cost total.</p>
        <?php endif; ?>
    </section>

    <section class="financial-pipeline" aria-label="Order pipeline values">
        <div class="financial-pipeline__heading">
            <p class="financial-eyebrow">Order pipeline at <?= h($stockValue['as_of']) ?></p>
            <h2>Fulfilment and invoicing</h2>
        </div>
        <div class="financial-pipeline__metrics">
            <article class="financial-pipeline__metric financial-pipeline__metric--discrepancy">
                <span>Uninvoiced value</span>
                <strong><?= $this->Number->currency($stockValue['fulfilled_not_invoiced'], 'GBP') ?></strong>
                <small>Fulfilled and dispatched badge goods not yet invoiced; postage excluded.</small>
            </article>
            <article class="financial-pipeline__metric financial-pipeline__metric--unfulfilled">
                <span>Unfulfilled value</span>
                <strong><?= $this->Number->currency($stockValue['unfulfilled_value'], 'GBP') ?></strong>
                <small>Remaining badge quantities on non-cancelled orders, at their recorded order prices.</small>
            </article>
        </div>
    </section>

    <section class="financial-monthly">
        <div class="financial-monthly__heading">
            <div>
                <p class="financial-eyebrow">Year at a glance</p>
                <h2>Monthly breakdown</h2>
            </div>
            <div class="financial-legend" aria-label="Chart legend">
                <span><i class="financial-legend__income"></i>Invoiced</span>
                <span><i class="financial-legend__cost"></i>Tracked costs</span>
            </div>
        </div>
        <div class="financial-table-scroll">
            <table class="financial-table">
                <thead>
                    <tr>
                        <th scope="col">Month</th>
                        <th scope="col" class="financial-table__chart-heading">Invoiced / costs</th>
                        <th scope="col" class="financial-table__number">Invoices</th>
                        <th scope="col" class="financial-table__number">Invoiced</th>
                        <th scope="col" class="financial-table__number">Stock received</th>
                        <th scope="col" class="financial-table__number">Postage</th>
                        <th scope="col" class="financial-table__number">After costs</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($months as $month) : ?>
                        <?php
                        $incomeWidth = min(100, $month['invoiced'] / $monthlyScale * 100);
                        $costWidth = min(100, ($month['stock_received'] + $month['postage']) / $monthlyScale * 100);
                        ?>
                        <tr>
                            <th scope="row"><?= h($month['name']) ?></th>
                            <td class="financial-table__chart">
                                <span class="financial-bar financial-bar--income" style="width: <?= h((string)$incomeWidth) ?>%"></span>
                                <span class="financial-bar financial-bar--cost" style="width: <?= h((string)$costWidth) ?>%"></span>
                            </td>
                            <td class="financial-table__number"><?= $this->Number->format($month['invoice_count']) ?></td>
                            <td class="financial-table__number"><?= $this->Number->currency($month['invoiced'], 'GBP') ?></td>
                            <td class="financial-table__number"><?= $this->Number->currency($month['stock_received'], 'GBP') ?></td>
                            <td class="financial-table__number"><?= $this->Number->currency($month['postage'], 'GBP') ?></td>
                            <td class="financial-table__number financial-table__net <?= $month['net'] < 0 ? 'is-negative' : '' ?>"><?= $this->Number->currency($month['net'], 'GBP') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th scope="row">Total</th>
                        <td></td>
                        <td class="financial-table__number"><?= $this->Number->format($totals['invoice_count']) ?></td>
                        <td class="financial-table__number"><?= $this->Number->currency($totals['invoiced'], 'GBP') ?></td>
                        <td class="financial-table__number"><?= $this->Number->currency($totals['stock_received'], 'GBP') ?></td>
                        <td class="financial-table__number"><?= $this->Number->currency($totals['postage'], 'GBP') ?></td>
                        <td class="financial-table__number financial-table__net <?= $totals['net'] < 0 ? 'is-negative' : '' ?>"><?= $this->Number->currency($totals['net'], 'GBP') ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <p class="financial-note">Invoices are grouped by invoice date. Stock is grouped by receipt transaction date; inbound postage is grouped by final receipt date. Postage is included only where an actual cost has been recorded. This view does not track customer payments or other expenses.</p>
    </section>
</section>
