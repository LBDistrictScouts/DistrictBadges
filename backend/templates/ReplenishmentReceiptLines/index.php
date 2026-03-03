<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\ReplenishmentReceiptLine> $replenishmentReceiptLines
 */
?>
<div class="replenishmentReceiptLines index content">
    <?= $this->Html->link(__('New Replenishment Receipt Line'), ['action' => 'add'], ['class' => 'button float-right']) ?>
    <h3><?= __('Replenishment Receipt Lines') ?></h3>
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
                <?php foreach ($replenishmentReceiptLines as $replenishmentReceiptLine): ?>
                <tr>
                    <td><?= h($replenishmentReceiptLine->id) ?></td>
                    <td><?= h($replenishmentReceiptLine->transaction_timestamp) ?></td>
                    <td><?= $replenishmentReceiptLine->hasValue('badge') ? $this->Html->link($replenishmentReceiptLine->badge->badge_name, ['controller' => 'Badges', 'action' => 'view', $replenishmentReceiptLine->badge->id]) : '' ?></td>
                    <td><?= h($replenishmentReceiptLine->audit_hash) ?></td>
                    <td><?= $replenishmentReceiptLine->hasValue('fulfilment') ? $this->Html->link($replenishmentReceiptLine->fulfilment->fulfilment_number, ['controller' => 'Fulfilments', 'action' => 'view', $replenishmentReceiptLine->fulfilment->id]) : '' ?></td>
                    <td><?= $replenishmentReceiptLine->hasValue('audit') ? $this->Html->link($replenishmentReceiptLine->audit->id, ['controller' => 'Audits', 'action' => 'view', $replenishmentReceiptLine->audit->id]) : '' ?></td>
                    <td><?= $replenishmentReceiptLine->hasValue('replenishment') ? $this->Html->link($replenishmentReceiptLine->replenishment->wholesale_order_number, ['controller' => 'Replenishments', 'action' => 'view', $replenishmentReceiptLine->replenishment->id]) : '' ?></td>
                    <td><?= $this->Number->format($replenishmentReceiptLine->on_hand_quantity_change) ?></td>
                    <td><?= $this->Number->format($replenishmentReceiptLine->receipted_quantity_change) ?></td>
                    <td><?= $this->Number->format($replenishmentReceiptLine->pending_quantity_change) ?></td>
                    <td><?= $replenishmentReceiptLine->transaction_type === null ? '' : h($replenishmentReceiptLine->transaction_type->label()) ?></td>
                    <td class="actions">
                        <?= $this->Html->link(__('View'), ['action' => 'view', $replenishmentReceiptLine->id]) ?>
                        <?= $this->Html->link(__('Edit'), ['action' => 'edit', $replenishmentReceiptLine->id]) ?>
                        <?= $this->Form->postLink(
                            __('Delete'),
                            ['action' => 'delete', $replenishmentReceiptLine->id],
                            [
                                'method' => 'delete',
                                'confirm' => __('Are you sure you want to delete # {0}?', $replenishmentReceiptLine->id),
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