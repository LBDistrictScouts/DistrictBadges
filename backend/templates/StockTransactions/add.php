<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\StockTransaction $stockTransaction
 * @var \Cake\Collection\CollectionInterface|string[] $badges
 * @var \Cake\Collection\CollectionInterface|string[] $fulfilments
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('List Stock Transactions'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="stockTransactions form content">
            <?= $this->Form->create($stockTransaction) ?>
            <fieldset>
                <legend><?= __('Add Stock Transaction') ?></legend>
                <?php
                    echo $this->Form->control('transaction_type');
                    echo $this->Form->control('transaction_timestamp');
                    echo $this->Form->control('badge_id', ['options' => $badges]);
                    echo $this->Form->control('change_amount');
                    echo $this->Form->control('audit_hash');
                    echo $this->Form->control('fulfilment_id', ['options' => $fulfilments]);
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
