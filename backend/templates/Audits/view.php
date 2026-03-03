<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Audit $audit
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('Edit Audit'), ['action' => 'edit', $audit->id], ['class' => 'side-nav-item']) ?>
            <?= $this->Form->postLink(__('Delete Audit'), ['action' => 'delete', $audit->id], ['confirm' => __('Are you sure you want to delete # {0}?', $audit->id), 'class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('List Audits'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('New Audit'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="audits view content">
            <h3><?= h($audit->id) ?></h3>
            <table>
                <tr>
                    <th><?= __('Id') ?></th>
                    <td><?= h($audit->id) ?></td>
                </tr>
                <tr>
                    <th><?= __('User') ?></th>
                    <td><?= $audit->hasValue('user') ? $this->Html->link($audit->user->first_name, ['controller' => 'Users', 'action' => 'view', $audit->user->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Audit Timestamp') ?></th>
                    <td><?= h($audit->audit_timestamp) ?></td>
                </tr>
                <tr>
                    <th><?= __('Audit Completed') ?></th>
                    <td><?= $audit->audit_completed ? __('Yes') : __('No'); ?></td>
                </tr>
            </table>
            <div class="related">
                <h4><?= __('Related Stock Transactions') ?></h4>
                <?php if (!empty($audit->stock_transactions)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Id') ?></th>
                            <th><?= __('Transaction Timestamp') ?></th>
                            <th><?= __('Badge Id') ?></th>
                            <th><?= __('Audit Hash') ?></th>
                            <th><?= __('Fulfilment Id') ?></th>
                            <th><?= __('Replenishment Id') ?></th>
                            <th><?= __('On Hand Quantity Change') ?></th>
                            <th><?= __('Receipted Quantity Change') ?></th>
                            <th><?= __('Pending Quantity Change') ?></th>
                            <th><?= __('Transaction Type') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($audit->stock_transactions as $stockTransaction) : ?>
                        <tr>
                            <td><?= h($stockTransaction->id) ?></td>
                            <td><?= h($stockTransaction->transaction_timestamp) ?></td>
                            <td><?= h($stockTransaction->badge_id) ?></td>
                            <td><?= h($stockTransaction->audit_hash) ?></td>
                            <td><?= h($stockTransaction->fulfilment_id) ?></td>
                            <td><?= h($stockTransaction->replenishment_id) ?></td>
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