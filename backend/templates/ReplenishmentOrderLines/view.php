<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\ReplenishmentOrderLine $replenishmentOrderLine
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('Edit Replenishment Order Line'), ['action' => 'edit', $replenishmentOrderLine->id], ['class' => 'side-nav-item']) ?>
            <?= $this->Form->postLink(__('Delete Replenishment Order Line'), ['action' => 'delete', $replenishmentOrderLine->id], ['confirm' => __('Are you sure you want to delete # {0}?', $replenishmentOrderLine->id), 'class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('List Replenishment Order Lines'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('New Replenishment Order Line'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="replenishmentOrderLines view content">
            <h3><?= h($replenishmentOrderLine->transaction_type) ?></h3>
            <table>
                <tr>
                    <th><?= __('Id') ?></th>
                    <td><?= h($replenishmentOrderLine->id) ?></td>
                </tr>
                <tr>
                    <th><?= __('Badge') ?></th>
                    <td><?= $replenishmentOrderLine->hasValue('badge') ? $this->Html->link($replenishmentOrderLine->badge->badge_name, ['controller' => 'Badges', 'action' => 'view', $replenishmentOrderLine->badge->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Audit Hash') ?></th>
                    <td><?= h($replenishmentOrderLine->audit_hash) ?></td>
                </tr>
                <tr>
                    <th><?= __('Fulfilment') ?></th>
                    <td><?= $replenishmentOrderLine->hasValue('fulfilment') ? $this->Html->link($replenishmentOrderLine->fulfilment->fulfilment_number, ['controller' => 'Fulfilments', 'action' => 'view', $replenishmentOrderLine->fulfilment->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Audit') ?></th>
                    <td><?= $replenishmentOrderLine->hasValue('audit') ? $this->Html->link($replenishmentOrderLine->audit->id, ['controller' => 'Audits', 'action' => 'view', $replenishmentOrderLine->audit->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Replenishment') ?></th>
                    <td><?= $replenishmentOrderLine->hasValue('replenishment') ? $this->Html->link($replenishmentOrderLine->replenishment->wholesale_order_number, ['controller' => 'Replenishments', 'action' => 'view', $replenishmentOrderLine->replenishment->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('On Hand Quantity Change') ?></th>
                    <td><?= $this->Number->format($replenishmentOrderLine->on_hand_quantity_change) ?></td>
                </tr>
                <tr>
                    <th><?= __('Receipted Quantity Change') ?></th>
                    <td><?= $this->Number->format($replenishmentOrderLine->receipted_quantity_change) ?></td>
                </tr>
                <tr>
                    <th><?= __('Pending Quantity Change') ?></th>
                    <td><?= $this->Number->format($replenishmentOrderLine->pending_quantity_change) ?></td>
                </tr>
                <tr>
                    <th><?= __('Transaction Type') ?></th>
                    <td><?= h($replenishmentOrderLine->transaction_type->label()) ?></td>
                </tr>
                <tr>
                    <th><?= __('Transaction Timestamp') ?></th>
                    <td><?= h($replenishmentOrderLine->transaction_timestamp) ?></td>
                </tr>
            </table>
        </div>
    </div>
</div>