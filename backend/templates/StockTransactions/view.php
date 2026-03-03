<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\StockTransaction $stockTransaction
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('Edit Stock Transaction'), ['action' => 'edit', $stockTransaction->id], ['class' => 'side-nav-item']) ?>
            <?= $this->Form->postLink(__('Delete Stock Transaction'), ['action' => 'delete', $stockTransaction->id], ['confirm' => __('Are you sure you want to delete # {0}?', $stockTransaction->id), 'class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('List Stock Transactions'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('New Stock Transaction'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="stockTransactions view content">
            <h3><?= h($stockTransaction->transaction_type) ?></h3>
            <table>
                <tr>
                    <th><?= __('Id') ?></th>
                    <td><?= h($stockTransaction->id) ?></td>
                </tr>
                <tr>
                    <th><?= __('Badge') ?></th>
                    <td><?= $stockTransaction->hasValue('badge') ? $this->Html->link($stockTransaction->badge->badge_name, ['controller' => 'Badges', 'action' => 'view', $stockTransaction->badge->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Audit Hash') ?></th>
                    <td><?= h($stockTransaction->audit_hash) ?></td>
                </tr>
                <tr>
                    <th><?= __('Fulfilment') ?></th>
                    <td><?= $stockTransaction->hasValue('fulfilment') ? $this->Html->link($stockTransaction->fulfilment->fulfilment_number, ['controller' => 'Fulfilments', 'action' => 'view', $stockTransaction->fulfilment->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Audit Id') ?></th>
                    <td><?= h($stockTransaction->audit_id) ?></td>
                </tr>
                <tr>
                    <th><?= __('Replenishment Id') ?></th>
                    <td><?= h($stockTransaction->replenishment_id) ?></td>
                </tr>
                <tr>
                    <th><?= __('On Hand Quantity Change') ?></th>
                    <td><?= $this->Number->format($stockTransaction->on_hand_quantity_change) ?></td>
                </tr>
                <tr>
                    <th><?= __('Receipted Quantity Change') ?></th>
                    <td><?= $this->Number->format($stockTransaction->receipted_quantity_change) ?></td>
                </tr>
                <tr>
                    <th><?= __('Pending Quantity Change') ?></th>
                    <td><?= $this->Number->format($stockTransaction->pending_quantity_change) ?></td>
                </tr>
                <tr>
                    <th><?= __('Transaction Type') ?></th>
                    <td><?= h($stockTransaction->transaction_type->label()) ?></td>
                </tr>
                <tr>
                    <th><?= __('Transaction Timestamp') ?></th>
                    <td><?= h($stockTransaction->transaction_timestamp) ?></td>
                </tr>
            </table>
        </div>
    </div>
</div>