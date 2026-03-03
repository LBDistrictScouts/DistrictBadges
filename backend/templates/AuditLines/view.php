<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\AuditLine $auditLine
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('Edit Audit Line'), ['action' => 'edit', $auditLine->id], ['class' => 'side-nav-item']) ?>
            <?= $this->Form->postLink(__('Delete Audit Line'), ['action' => 'delete', $auditLine->id], ['confirm' => __('Are you sure you want to delete # {0}?', $auditLine->id), 'class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('List Audit Lines'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('New Audit Line'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="auditLines view content">
            <h3><?= h($auditLine->transaction_type) ?></h3>
            <table>
                <tr>
                    <th><?= __('Id') ?></th>
                    <td><?= h($auditLine->id) ?></td>
                </tr>
                <tr>
                    <th><?= __('Badge') ?></th>
                    <td><?= $auditLine->hasValue('badge') ? $this->Html->link($auditLine->badge->badge_name, ['controller' => 'Badges', 'action' => 'view', $auditLine->badge->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Audit Hash') ?></th>
                    <td><?= h($auditLine->audit_hash) ?></td>
                </tr>
                <tr>
                    <th><?= __('Fulfilment') ?></th>
                    <td><?= $auditLine->hasValue('fulfilment') ? $this->Html->link($auditLine->fulfilment->fulfilment_number, ['controller' => 'Fulfilments', 'action' => 'view', $auditLine->fulfilment->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Audit') ?></th>
                    <td><?= $auditLine->hasValue('audit') ? $this->Html->link($auditLine->audit->id, ['controller' => 'Audits', 'action' => 'view', $auditLine->audit->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Replenishment') ?></th>
                    <td><?= $auditLine->hasValue('replenishment') ? $this->Html->link($auditLine->replenishment->wholesale_order_number, ['controller' => 'Replenishments', 'action' => 'view', $auditLine->replenishment->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('On Hand Quantity Change') ?></th>
                    <td><?= $this->Number->format($auditLine->on_hand_quantity_change) ?></td>
                </tr>
                <tr>
                    <th><?= __('Receipted Quantity Change') ?></th>
                    <td><?= $this->Number->format($auditLine->receipted_quantity_change) ?></td>
                </tr>
                <tr>
                    <th><?= __('Pending Quantity Change') ?></th>
                    <td><?= $this->Number->format($auditLine->pending_quantity_change) ?></td>
                </tr>
                <tr>
                    <th><?= __('Transaction Type') ?></th>
                    <td><?= h($auditLine->transaction_type->label()) ?></td>
                </tr>
                <tr>
                    <th><?= __('Transaction Timestamp') ?></th>
                    <td><?= h($auditLine->transaction_timestamp) ?></td>
                </tr>
            </table>
        </div>
    </div>
</div>