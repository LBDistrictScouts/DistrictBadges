<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\FulfilmentLine> $fulfilmentLines
 */
?>
<div class="fulfilmentLines index content">
    <?= $this->Html->link(__('New Fulfilment Line'), ['action' => 'add'], ['class' => 'button float-right']) ?>
    <h3><?= __('Fulfilment Lines') ?></h3>
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
                <?php foreach ($fulfilmentLines as $fulfilmentLine): ?>
                <tr>
                    <td><?= h($fulfilmentLine->id) ?></td>
                    <td><?= h($fulfilmentLine->transaction_timestamp) ?></td>
                    <td><?= $fulfilmentLine->hasValue('badge') ? $this->Html->link($fulfilmentLine->badge->badge_name, ['controller' => 'Badges', 'action' => 'view', $fulfilmentLine->badge->id]) : '' ?></td>
                    <td><?= h($fulfilmentLine->audit_hash) ?></td>
                    <td><?= $fulfilmentLine->hasValue('fulfilment') ? $this->Html->link($fulfilmentLine->fulfilment->fulfilment_number, ['controller' => 'Fulfilments', 'action' => 'view', $fulfilmentLine->fulfilment->id]) : '' ?></td>
                    <td><?= $fulfilmentLine->hasValue('audit') ? $this->Html->link($fulfilmentLine->audit->id, ['controller' => 'Audits', 'action' => 'view', $fulfilmentLine->audit->id]) : '' ?></td>
                    <td><?= $fulfilmentLine->hasValue('replenishment') ? $this->Html->link($fulfilmentLine->replenishment->wholesale_order_number, ['controller' => 'Replenishments', 'action' => 'view', $fulfilmentLine->replenishment->id]) : '' ?></td>
                    <td><?= $this->Number->format($fulfilmentLine->on_hand_quantity_change) ?></td>
                    <td><?= $this->Number->format($fulfilmentLine->receipted_quantity_change) ?></td>
                    <td><?= $this->Number->format($fulfilmentLine->pending_quantity_change) ?></td>
                    <td><?= $fulfilmentLine->transaction_type === null ? '' : h($fulfilmentLine->transaction_type->label()) ?></td>
                    <td class="actions">
                        <?= $this->Html->link(__('View'), ['action' => 'view', $fulfilmentLine->id]) ?>
                        <?= $this->Html->link(__('Edit'), ['action' => 'edit', $fulfilmentLine->id]) ?>
                        <?= $this->Form->postLink(
                            __('Delete'),
                            ['action' => 'delete', $fulfilmentLine->id],
                            [
                                'method' => 'delete',
                                'confirm' => __('Are you sure you want to delete # {0}?', $fulfilmentLine->id),
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