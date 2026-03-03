<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\FulfilmentLine $fulfilmentLine
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('Edit Fulfilment Line'), ['action' => 'edit', $fulfilmentLine->id], ['class' => 'side-nav-item']) ?>
            <?= $this->Form->postLink(__('Delete Fulfilment Line'), ['action' => 'delete', $fulfilmentLine->id], ['confirm' => __('Are you sure you want to delete # {0}?', $fulfilmentLine->id), 'class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('List Fulfilment Lines'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('New Fulfilment Line'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="fulfilmentLines view content">
            <h3><?= h($fulfilmentLine->transaction_type) ?></h3>
            <table>
                <tr>
                    <th><?= __('Id') ?></th>
                    <td><?= h($fulfilmentLine->id) ?></td>
                </tr>
                <tr>
                    <th><?= __('Badge') ?></th>
                    <td><?= $fulfilmentLine->hasValue('badge') ? $this->Html->link($fulfilmentLine->badge->badge_name, ['controller' => 'Badges', 'action' => 'view', $fulfilmentLine->badge->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Audit Hash') ?></th>
                    <td><?= h($fulfilmentLine->audit_hash) ?></td>
                </tr>
                <tr>
                    <th><?= __('Fulfilment') ?></th>
                    <td><?= $fulfilmentLine->hasValue('fulfilment') ? $this->Html->link($fulfilmentLine->fulfilment->fulfilment_number, ['controller' => 'Fulfilments', 'action' => 'view', $fulfilmentLine->fulfilment->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Audit') ?></th>
                    <td><?= $fulfilmentLine->hasValue('audit') ? $this->Html->link($fulfilmentLine->audit->id, ['controller' => 'Audits', 'action' => 'view', $fulfilmentLine->audit->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Replenishment') ?></th>
                    <td><?= $fulfilmentLine->hasValue('replenishment') ? $this->Html->link($fulfilmentLine->replenishment->wholesale_order_number, ['controller' => 'Replenishments', 'action' => 'view', $fulfilmentLine->replenishment->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('On Hand Quantity Change') ?></th>
                    <td><?= $this->Number->format($fulfilmentLine->on_hand_quantity_change) ?></td>
                </tr>
                <tr>
                    <th><?= __('Receipted Quantity Change') ?></th>
                    <td><?= $this->Number->format($fulfilmentLine->receipted_quantity_change) ?></td>
                </tr>
                <tr>
                    <th><?= __('Pending Quantity Change') ?></th>
                    <td><?= $this->Number->format($fulfilmentLine->pending_quantity_change) ?></td>
                </tr>
                <tr>
                    <th><?= __('Transaction Type') ?></th>
                    <td><?= h($fulfilmentLine->transaction_type->label()) ?></td>
                </tr>
                <tr>
                    <th><?= __('Transaction Timestamp') ?></th>
                    <td><?= h($fulfilmentLine->transaction_timestamp) ?></td>
                </tr>
            </table>
        </div>
    </div>
</div>