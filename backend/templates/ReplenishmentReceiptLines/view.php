<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\ReplenishmentReceiptLine $replenishmentReceiptLine
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('Edit Replenishment Receipt Line'), ['action' => 'edit', $replenishmentReceiptLine->id], ['class' => 'side-nav-item']) ?>
            <?= $this->Form->postLink(__('Delete Replenishment Receipt Line'), ['action' => 'delete', $replenishmentReceiptLine->id], ['confirm' => __('Are you sure you want to delete # {0}?', $replenishmentReceiptLine->id), 'class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('List Replenishment Receipt Lines'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('New Replenishment Receipt Line'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="replenishmentReceiptLines view content">
            <h3><?= h($replenishmentReceiptLine->transaction_type) ?></h3>
            <table>
                <tr>
                    <th><?= __('Id') ?></th>
                    <td><?= h($replenishmentReceiptLine->id) ?></td>
                </tr>
                <tr>
                    <th><?= __('Badge') ?></th>
                    <td><?= $replenishmentReceiptLine->hasValue('badge') ? $this->Html->link($replenishmentReceiptLine->badge->badge_name, ['controller' => 'Badges', 'action' => 'view', $replenishmentReceiptLine->badge->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Audit Hash') ?></th>
                    <td><?= h($replenishmentReceiptLine->audit_hash) ?></td>
                </tr>
                <tr>
                    <th><?= __('Fulfilment') ?></th>
                    <td><?= $replenishmentReceiptLine->hasValue('fulfilment') ? $this->Html->link($replenishmentReceiptLine->fulfilment->fulfilment_number, ['controller' => 'Fulfilments', 'action' => 'view', $replenishmentReceiptLine->fulfilment->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Audit') ?></th>
                    <td><?= $replenishmentReceiptLine->hasValue('audit') ? $this->Html->link($replenishmentReceiptLine->audit->id, ['controller' => 'Audits', 'action' => 'view', $replenishmentReceiptLine->audit->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Replenishment') ?></th>
                    <td><?= $replenishmentReceiptLine->hasValue('replenishment') ? $this->Html->link($replenishmentReceiptLine->replenishment->wholesale_order_number, ['controller' => 'Replenishments', 'action' => 'view', $replenishmentReceiptLine->replenishment->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('On Hand Quantity Change') ?></th>
                    <td><?= $this->Number->format($replenishmentReceiptLine->on_hand_quantity_change) ?></td>
                </tr>
                <tr>
                    <th><?= __('Receipted Quantity Change') ?></th>
                    <td><?= $this->Number->format($replenishmentReceiptLine->receipted_quantity_change) ?></td>
                </tr>
                <tr>
                    <th><?= __('Pending Quantity Change') ?></th>
                    <td><?= $this->Number->format($replenishmentReceiptLine->pending_quantity_change) ?></td>
                </tr>
                <tr>
                    <th><?= __('Transaction Type') ?></th>
                    <td><?= h($replenishmentReceiptLine->transaction_type->label()) ?></td>
                </tr>
                <tr>
                    <th><?= __('Transaction Timestamp') ?></th>
                    <td><?= h($replenishmentReceiptLine->transaction_timestamp) ?></td>
                </tr>
            </table>
        </div>
    </div>
</div>