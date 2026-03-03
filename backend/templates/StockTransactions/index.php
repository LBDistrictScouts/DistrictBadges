<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\StockTransaction> $stockTransactions
 */
?>
<div class="stockTransactions index content">
    <?= $this->Html->link(__('New Stock Transaction'), ['action' => 'add'], ['class' => 'button float-right']) ?>
    <h3><?= __('Stock Transactions') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('id') ?></th>
                    <th><?= $this->Paginator->sort('transaction_timestamp') ?></th>
                    <th><?= $this->Paginator->sort('badge_id') ?></th>
                    <th><?= $this->Paginator->sort('audit_hash') ?></th>
                    <th><?= $this->Paginator->sort('fulfilment_id') ?></th>
                    <th><?= $this->Paginator->sort('audit_id') ?></th>
                    <th><?= $this->Paginator->sort('replenishment_id') ?></th>
                    <th><?= $this->Paginator->sort('on_hand_quantity_change') ?></th>
                    <th><?= $this->Paginator->sort('receipted_quantity_change') ?></th>
                    <th><?= $this->Paginator->sort('pending_quantity_change') ?></th>
                    <th><?= $this->Paginator->sort('transaction_type') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($stockTransactions as $stockTransaction): ?>
                <tr>
                    <td><?= h($stockTransaction->id) ?></td>
                    <td><?= h($stockTransaction->transaction_timestamp) ?></td>
                    <td><?= $stockTransaction->hasValue('badge') ? $this->Html->link($stockTransaction->badge->badge_name, ['controller' => 'Badges', 'action' => 'view', $stockTransaction->badge->id]) : '' ?></td>
                    <td><?= h($stockTransaction->audit_hash) ?></td>
                    <td><?= $stockTransaction->hasValue('fulfilment') ? $this->Html->link($stockTransaction->fulfilment->fulfilment_number, ['controller' => 'Fulfilments', 'action' => 'view', $stockTransaction->fulfilment->id]) : '' ?></td>
                    <td><?= h($stockTransaction->audit_id) ?></td>
                    <td><?= h($stockTransaction->replenishment_id) ?></td>
                    <td><?= $this->Number->format($stockTransaction->on_hand_quantity_change) ?></td>
                    <td><?= $this->Number->format($stockTransaction->receipted_quantity_change) ?></td>
                    <td><?= $this->Number->format($stockTransaction->pending_quantity_change) ?></td>
                    <td><?= $stockTransaction->transaction_type === null ? '' : h($stockTransaction->transaction_type->label()) ?></td>
                    <td class="actions">
                        <?= $this->Html->link(__('View'), ['action' => 'view', $stockTransaction->id]) ?>
                        <?= $this->Html->link(__('Edit'), ['action' => 'edit', $stockTransaction->id]) ?>
                        <?= $this->Form->postLink(
                            __('Delete'),
                            ['action' => 'delete', $stockTransaction->id],
                            [
                                'method' => 'delete',
                                'confirm' => __('Are you sure you want to delete # {0}?', $stockTransaction->id),
                            ]
                        ) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="paginator">
        <ul class="pagination">
            <?= $this->Paginator->first('<< ' . __('first')) ?>
            <?= $this->Paginator->prev('< ' . __('previous')) ?>
            <?= $this->Paginator->numbers() ?>
            <?= $this->Paginator->next(__('next') . ' >') ?>
            <?= $this->Paginator->last(__('last') . ' >>') ?>
        </ul>
        <p><?= $this->Paginator->counter(__('Page {{page}} of {{pages}}, showing {{current}} record(s) out of {{count}} total')) ?></p>
    </div>
</div>