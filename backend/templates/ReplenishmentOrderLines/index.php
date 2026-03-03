<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\ReplenishmentOrderLine> $replenishmentOrderLines
 */
?>
<div class="replenishmentOrderLines index content">
    <?= $this->Html->link(__('New Replenishment Order Line'), ['action' => 'add'], ['class' => 'button float-right']) ?>
    <h3><?= __('Replenishment Order Lines') ?></h3>
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
                <?php foreach ($replenishmentOrderLines as $replenishmentOrderLine): ?>
                <tr>
                    <td><?= h($replenishmentOrderLine->id) ?></td>
                    <td><?= h($replenishmentOrderLine->transaction_timestamp) ?></td>
                    <td><?= $replenishmentOrderLine->hasValue('badge') ? $this->Html->link($replenishmentOrderLine->badge->badge_name, ['controller' => 'Badges', 'action' => 'view', $replenishmentOrderLine->badge->id]) : '' ?></td>
                    <td><?= h($replenishmentOrderLine->audit_hash) ?></td>
                    <td><?= $replenishmentOrderLine->hasValue('fulfilment') ? $this->Html->link($replenishmentOrderLine->fulfilment->fulfilment_number, ['controller' => 'Fulfilments', 'action' => 'view', $replenishmentOrderLine->fulfilment->id]) : '' ?></td>
                    <td><?= $replenishmentOrderLine->hasValue('audit') ? $this->Html->link($replenishmentOrderLine->audit->id, ['controller' => 'Audits', 'action' => 'view', $replenishmentOrderLine->audit->id]) : '' ?></td>
                    <td><?= $replenishmentOrderLine->hasValue('replenishment') ? $this->Html->link($replenishmentOrderLine->replenishment->wholesale_order_number, ['controller' => 'Replenishments', 'action' => 'view', $replenishmentOrderLine->replenishment->id]) : '' ?></td>
                    <td><?= $this->Number->format($replenishmentOrderLine->on_hand_quantity_change) ?></td>
                    <td><?= $this->Number->format($replenishmentOrderLine->receipted_quantity_change) ?></td>
                    <td><?= $this->Number->format($replenishmentOrderLine->pending_quantity_change) ?></td>
                    <td><?= $replenishmentOrderLine->transaction_type === null ? '' : h($replenishmentOrderLine->transaction_type->label()) ?></td>
                    <td class="actions">
                        <?= $this->Html->link(__('View'), ['action' => 'view', $replenishmentOrderLine->id]) ?>
                        <?= $this->Html->link(__('Edit'), ['action' => 'edit', $replenishmentOrderLine->id]) ?>
                        <?= $this->Form->postLink(
                            __('Delete'),
                            ['action' => 'delete', $replenishmentOrderLine->id],
                            [
                                'method' => 'delete',
                                'confirm' => __('Are you sure you want to delete # {0}?', $replenishmentOrderLine->id),
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