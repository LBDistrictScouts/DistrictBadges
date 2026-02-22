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
            <?= $this->Form->postLink(__('Delete Stock Transaction'), ['action' => 'delete', $stockTransaction->id], ['confirm' => __('Are you sure you want to delete this stock transaction?'), 'class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('List Stock Transactions'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('New Stock Transaction'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="stockTransactions view content">
            <h3><?= h($stockTransaction->transaction_type) ?></h3>
            <table>
                <tr>
                    <th><?= __('Transaction Type') ?></th>
                    <td>
                        <?php
                            $transactionTypeValue = $stockTransaction->transaction_type;
                            $transactionTypeLabel = $transactionTypeValue;
                            if ($transactionTypeValue instanceof \App\Model\Enum\TransactionType) {
                                $transactionTypeLabel = $transactionTypeValue->label();
                            } elseif (is_string($transactionTypeValue)) {
                                try {
                                    $transactionTypeLabel = \App\Model\Enum\TransactionType::from($transactionTypeValue)->label();
                                } catch (\ValueError $e) {
                                }
                            }
                        ?>
                        <?= h($transactionTypeLabel) ?>
                    </td>
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
                    <th><?= __('Change Amount') ?></th>
                    <td><?= $this->Number->format($stockTransaction->change_amount) ?></td>
                </tr>
                <tr>
                    <th><?= __('Transaction Timestamp') ?></th>
                    <td><?= h($stockTransaction->transaction_timestamp) ?></td>
                </tr>
            </table>
        </div>
    </div>
</div>
