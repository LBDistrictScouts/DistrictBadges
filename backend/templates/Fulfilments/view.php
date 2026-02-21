<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Fulfilment $fulfilment
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('Edit Fulfilment'), ['action' => 'edit', $fulfilment->id], ['class' => 'side-nav-item']) ?>
            <?= $this->Form->postLink(__('Delete Fulfilment'), ['action' => 'delete', $fulfilment->id], ['confirm' => __('Are you sure you want to delete this fulfilment?'), 'class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('List Fulfilments'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('New Fulfilment'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="fulfilments view content">
            <h3><?= h($fulfilment->fulfilment_number) ?></h3>
            <table>
                <tr>
                    <th><?= __('Fulfilment Number') ?></th>
                    <td><?= h($fulfilment->fulfilment_number) ?></td>
                </tr>
                <tr>
                    <th><?= __('Fulfilment Date') ?></th>
                    <td><?= h($fulfilment->fulfilment_date) ?></td>
                </tr>
            </table>
            <div class="related">
                <h4><?= __('Related Stock Transactions') ?></h4>
                <?php if (!empty($fulfilment->stock_transactions)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Transaction Type') ?></th>
                            <th><?= __('Transaction Timestamp') ?></th>
                            <th><?= __('Change Amount') ?></th>
                            <th><?= __('Audit Hash') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($fulfilment->stock_transactions as $stockTransaction) : ?>
                        <tr>
                            <td><?= h($stockTransaction->transaction_type) ?></td>
                            <td><?= h($stockTransaction->transaction_timestamp) ?></td>
                            <td><?= h($stockTransaction->change_amount) ?></td>
                            <td><?= h($stockTransaction->audit_hash) ?></td>
                            <td class="actions">
                                <?= $this->Html->link(__('View'), ['controller' => 'StockTransactions', 'action' => 'view', $stockTransaction->id]) ?>
                                <?= $this->Html->link(__('Edit'), ['controller' => 'StockTransactions', 'action' => 'edit', $stockTransaction->id]) ?>
                                <?= $this->Form->postLink(
                                    __('Delete'),
                                    ['controller' => 'StockTransactions', 'action' => 'delete', $stockTransaction->id],
                                    [
                                        'method' => 'delete',
                                        'confirm' => __('Are you sure you want to delete this stock transaction?'),
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
