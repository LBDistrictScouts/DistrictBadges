<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Replenishment $replenishment
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('Edit Replenishment'), ['action' => 'edit', $replenishment->id], ['class' => 'side-nav-item']) ?>
            <?= $this->Form->postLink(__('Delete Replenishment'), ['action' => 'delete', $replenishment->id], ['confirm' => __('Are you sure you want to delete # {0}?', $replenishment->id), 'class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('List Replenishments'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('New Replenishment'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="replenishments view content">
            <h3><?= h($replenishment->wholesale_order_number) ?></h3>
            <table>
                <tr>
                    <th><?= __('Id') ?></th>
                    <td><?= h($replenishment->id) ?></td>
                </tr>
                <tr>
                    <th><?= __('Wholesale Order Number') ?></th>
                    <td><?= h($replenishment->wholesale_order_number) ?></td>
                </tr>
                <tr>
                    <th><?= __('Total Amount') ?></th>
                    <td><?= $this->Number->format($replenishment->total_amount) ?></td>
                </tr>
                <tr>
                    <th><?= __('Total Quantity') ?></th>
                    <td><?= $this->Number->format($replenishment->total_quantity) ?></td>
                </tr>
                <tr>
                    <th><?= __('Created Date') ?></th>
                    <td><?= h($replenishment->created_date) ?></td>
                </tr>
                <tr>
                    <th><?= __('Order Submitted Date') ?></th>
                    <td><?= h($replenishment->order_submitted_date) ?></td>
                </tr>
                <tr>
                    <th><?= __('Received Date') ?></th>
                    <td><?= h($replenishment->received_date) ?></td>
                </tr>
                <tr>
                    <th><?= __('Order Submitted') ?></th>
                    <td><?= $replenishment->order_submitted ? __('Yes') : __('No'); ?></td>
                </tr>
                <tr>
                    <th><?= __('Received') ?></th>
                    <td><?= $replenishment->received ? __('Yes') : __('No'); ?></td>
                </tr>
            </table>
            <div class="related">
                <h4><?= __('Related Stock Transactions') ?></h4>
                <?php if (!empty($replenishment->stock_transactions)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Id') ?></th>
                            <th><?= __('Transaction Timestamp') ?></th>
                            <th><?= __('Badge Id') ?></th>
                            <th><?= __('Audit Hash') ?></th>
                            <th><?= __('Fulfilment Id') ?></th>
                            <th><?= __('Audit Id') ?></th>
                            <th><?= __('On Hand Quantity Change') ?></th>
                            <th><?= __('Receipted Quantity Change') ?></th>
                            <th><?= __('Pending Quantity Change') ?></th>
                            <th><?= __('Transaction Type') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($replenishment->stock_transactions as $stockTransaction) : ?>
                        <tr>
                            <td><?= h($stockTransaction->id) ?></td>
                            <td><?= h($stockTransaction->transaction_timestamp) ?></td>
                            <td><?= h($stockTransaction->badge_id) ?></td>
                            <td><?= h($stockTransaction->audit_hash) ?></td>
                            <td><?= h($stockTransaction->fulfilment_id) ?></td>
                            <td><?= h($stockTransaction->audit_id) ?></td>
                            <td><?= h($stockTransaction->on_hand_quantity_change) ?></td>
                            <td><?= h($stockTransaction->receipted_quantity_change) ?></td>
                            <td><?= h($stockTransaction->pending_quantity_change) ?></td>
                            <td><?= h($stockTransaction->transaction_type) ?></td>
                            <td class="actions">
                                <?= $this->Html->link(__('View'), ['controller' => 'StockTransactions', 'action' => 'view', $stockTransaction->id]) ?>
                                <?= $this->Html->link(__('Edit'), ['controller' => 'StockTransactions', 'action' => 'edit', $stockTransaction->id]) ?>
                                <?= $this->Form->postLink(
                                    __('Delete'),
                                    ['controller' => 'StockTransactions', 'action' => 'delete', $stockTransaction->id],
                                    [
                                        'method' => 'delete',
                                        'confirm' => __('Are you sure you want to delete # {0}?', $stockTransaction->id),
                                    ]
                                ) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>