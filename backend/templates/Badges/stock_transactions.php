<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Badge $badge
 * @var iterable<\App\Model\Entity\StockTransaction> $stockTransactions
 * @var array<string, string> $filters
 * @var array<int, string> $transactionTypeOptions
 */
?>
<div class="row">
    <aside class="column badge-view-sidebar">
        <?= $this->element('badge_product_card', ['badge' => $badge]) ?>
        <div class="side-nav">
            <h4 class="heading"><?= __('Badge') ?></h4>
            <?= $this->Html->link(__('Badge Details'), ['action' => 'view', $badge->id], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('Edit Badge'), ['action' => 'edit', $badge->id], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('List Badges'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="badges stock-transactions content">
            <h3><?= __('Stock Transactions: {0}', h($badge->badge_name)) ?></h3>
            <?= $this->Form->create(null, [
                'type' => 'get',
                'class' => 'index-filters stock-transaction-filters',
            ]) ?>
            <div class="index-filters__row">
                <?= $this->Form->control('transaction_type', [
                    'label' => __('Transaction Type'),
                    'options' => $transactionTypeOptions,
                    'empty' => __('All transaction types'),
                    'value' => $filters['transaction_type'],
                ]) ?>
                <?= $this->Form->control('date_from', [
                    'type' => 'date',
                    'label' => __('From'),
                    'value' => $filters['date_from'],
                ]) ?>
                <?= $this->Form->control('date_to', [
                    'type' => 'date',
                    'label' => __('To'),
                    'value' => $filters['date_to'],
                ]) ?>
                <div class="index-filters__actions">
                    <?= $this->Form->button(__('Filter')) ?>
                    <?= $this->Html->link(
                        __('Clear'),
                        ['action' => 'stockTransactions', $badge->id],
                        ['class' => 'button button-outline'],
                    ) ?>
                </div>
            </div>
            <?= $this->Form->end() ?>
            <div class="table-responsive">
                <table class="stock-transactions-table">
                    <thead>
                        <tr>
                            <th><?= $this->Paginator->sort('transaction_timestamp', __('Date')) ?></th>
                            <th><?= $this->Paginator->sort('transaction_type', __('Type')) ?></th>
                            <th><?= __('Reference') ?></th>
                            <th><?= $this->Paginator->sort('on_hand_quantity_change', __('On Hand ±')) ?></th>
                            <th><?= $this->Paginator->sort('pending_quantity_change', __('Pending ±')) ?></th>
                            <th><?= $this->Paginator->sort('receipted_quantity_change', __('Receipted ±')) ?></th>
                            <th><?= $this->Paginator->sort('fulfilled_quantity_change', __('Fulfilled ±')) ?></th>
                            <th><?= __('Audit E/A') ?></th>
                            <th><?= $this->Paginator->sort('unit_price', __('Unit Price')) ?></th>
                            <th><?= $this->Paginator->sort('monetary_amount', __('Amount')) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stockTransactions as $transaction) : ?>
                        <tr>
                            <td><?= h($transaction->transaction_timestamp) ?></td>
                            <td><?= h($transactionTypeOptions[$transaction->transaction_type->value]) ?></td>
                            <td>
                                <?php if ($transaction->hasValue('audit')) : ?>
                                    <?= $this->Html->link(
                                        $transaction->audit->audit_number,
                                        ['controller' => 'Audits', 'action' => 'view', $transaction->audit->id],
                                    ) ?>
                                <?php elseif ($transaction->hasValue('fulfilment')) : ?>
                                    <?= $this->Html->link(
                                        $transaction->fulfilment->fulfilment_number,
                                        ['controller' => 'Fulfilments', 'action' => 'view', $transaction->fulfilment->id],
                                    ) ?>
                                <?php elseif ($transaction->order_line_id !== null) : ?>
                                    <?= $this->Html->link(
                                        __('Order line'),
                                        ['controller' => 'OrderLines', 'action' => 'view', $transaction->order_line_id],
                                    ) ?>
                                <?php elseif ($transaction->hasValue('replenishment')) : ?>
                                    <?= $this->Html->link(
                                        $transaction->replenishment->replenishment_number,
                                        ['controller' => 'Replenishments', 'action' => 'view', $transaction->replenishment->id],
                                    ) ?>
                                <?php else : ?>
                                    <span aria-label="<?= __('No reference') ?>">—</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $this->Number->format($transaction->on_hand_quantity_change) ?></td>
                            <td><?= $this->Number->format($transaction->pending_quantity_change) ?></td>
                            <td><?= $this->Number->format($transaction->receipted_quantity_change) ?></td>
                            <td><?= $this->Number->format($transaction->fulfilled_quantity_change) ?></td>
                            <td>
                                <?= $transaction->audit_expected_quantity === null
                                    ? '—'
                                    : h($transaction->audit_expected_quantity . ' / ' . $transaction->audit_actual_quantity) ?>
                            </td>
                            <td><?= $transaction->unit_price === null ? '—' : $this->Number->currency($transaction->unit_price) ?></td>
                            <td><?= $transaction->monetary_amount === null ? '—' : $this->Number->currency($transaction->monetary_amount) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="paginator">
                <?= $this->element('pagination_limit') ?>
                <ul class="pagination">
                    <?= $this->Paginator->first('<< ' . __('first')) ?>
                    <?= $this->Paginator->prev('< ' . __('previous')) ?>
                    <?= $this->Paginator->numbers() ?>
                    <?= $this->Paginator->next(__('next') . ' >') ?>
                    <?= $this->Paginator->last(__('last') . ' >>') ?>
                </ul>
                <p><?= $this->Paginator->counter(
                    __('Page {{page}} of {{pages}}, showing {{current}} record(s) out of {{count}} total'),
                ) ?></p>
            </div>
        </div>
    </div>
</div>
