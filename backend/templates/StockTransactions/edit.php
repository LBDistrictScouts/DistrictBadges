<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\StockTransaction $stockTransaction
 * @var string[]|\Cake\Collection\CollectionInterface $badges
 * @var string[]|\Cake\Collection\CollectionInterface $fulfilments
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Form->postLink(
                __('Delete'),
                ['action' => 'delete', $stockTransaction->id],
                ['confirm' => __('Are you sure you want to delete # {0}?', $stockTransaction->id), 'class' => 'side-nav-item']
            ) ?>
            <?= $this->Html->link(__('List Stock Transactions'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="stockTransactions form content">
            <?= $this->Form->create($stockTransaction) ?>
            <fieldset>
                <legend><?= __('Edit Stock Transaction') ?></legend>
                <?php
                    echo $this->Form->control('badge_id', ['options' => $badges]);
                    echo $this->Form->control('audit_hash');
                    echo $this->Form->control('fulfilment_id', ['options' => $fulfilments, 'empty' => true]);
                    echo $this->Form->control('audit_id');
                    echo $this->Form->control('replenishment_id');
                    echo $this->Form->control('on_hand_quantity_change');
                    echo $this->Form->control('receipted_quantity_change');
                    echo $this->Form->control('pending_quantity_change');
                    echo $this->Form->control('transaction_type');
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
