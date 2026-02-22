<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\StockTransaction $stockTransaction
 * @var \Cake\Collection\CollectionInterface|string[] $badges
 * @var \Cake\Collection\CollectionInterface|string[] $fulfilments
 * @var array<string, string> $transactionTypes
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
                    echo $this->Form->control('transaction_type', ['options' => $transactionTypes]);
                    echo $this->Form->control('badge_id', ['options' => $badges]);
                    echo $this->Form->control('change_amount');
                    echo $this->Form->control('audit_hash');
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
