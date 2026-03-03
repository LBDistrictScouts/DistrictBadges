<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\InvoiceLine $invoiceLine
 * @var \Cake\Collection\CollectionInterface|string[] $invoices
 * @var \Cake\Collection\CollectionInterface|string[] $badges
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('List Invoice Lines'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="invoiceLines form content">
            <?= $this->Form->create($invoiceLine) ?>
            <fieldset>
                <legend><?= __('Add Invoice Line') ?></legend>
                <?php
                    echo $this->Form->control('invoice_id', ['options' => $invoices]);
                    echo $this->Form->control('badge_id', ['options' => $badges]);
                    echo $this->Form->control('description');
                    echo $this->Form->control('quantity');
                    echo $this->Form->control('unit_price');
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
