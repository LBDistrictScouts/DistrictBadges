<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\AuditLine> $auditLines
 */
?>
<div class="auditLines index content">
    <?= $this->Html->link(__('New Audit Line'), ['action' => 'add'], ['class' => 'button float-right']) ?>
    <h3><?= __('Audit Lines') ?></h3>
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
                <?php foreach ($auditLines as $auditLine): ?>
                <tr>
                    <td><?= h($auditLine->id) ?></td>
                    <td><?= h($auditLine->transaction_timestamp) ?></td>
                    <td><?= $auditLine->hasValue('badge') ? $this->Html->link($auditLine->badge->badge_name, ['controller' => 'Badges', 'action' => 'view', $auditLine->badge->id]) : '' ?></td>
                    <td><?= h($auditLine->audit_hash) ?></td>
                    <td><?= $auditLine->hasValue('fulfilment') ? $this->Html->link($auditLine->fulfilment->fulfilment_number, ['controller' => 'Fulfilments', 'action' => 'view', $auditLine->fulfilment->id]) : '' ?></td>
                    <td><?= $auditLine->hasValue('audit') ? $this->Html->link($auditLine->audit->id, ['controller' => 'Audits', 'action' => 'view', $auditLine->audit->id]) : '' ?></td>
                    <td><?= $auditLine->hasValue('replenishment') ? $this->Html->link($auditLine->replenishment->wholesale_order_number, ['controller' => 'Replenishments', 'action' => 'view', $auditLine->replenishment->id]) : '' ?></td>
                    <td><?= $this->Number->format($auditLine->on_hand_quantity_change) ?></td>
                    <td><?= $this->Number->format($auditLine->receipted_quantity_change) ?></td>
                    <td><?= $this->Number->format($auditLine->pending_quantity_change) ?></td>
                    <td><?= $auditLine->transaction_type === null ? '' : h($auditLine->transaction_type->label()) ?></td>
                    <td class="actions">
                        <?= $this->Html->link(__('View'), ['action' => 'view', $auditLine->id]) ?>
                        <?= $this->Html->link(__('Edit'), ['action' => 'edit', $auditLine->id]) ?>
                        <?= $this->Form->postLink(
                            __('Delete'),
                            ['action' => 'delete', $auditLine->id],
                            [
                                'method' => 'delete',
                                'confirm' => __('Are you sure you want to delete # {0}?', $auditLine->id),
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