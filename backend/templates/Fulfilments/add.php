<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Fulfilment $fulfilment
 * @var array<string> $badges
 * @var array<string, mixed> $lineGrid
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('List Fulfilments'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="fulfilments form content">
            <?= $this->Form->create($fulfilment) ?>
            <fieldset>
                <legend><?= __('Add Fulfilment') ?></legend>
                <?php
                ?>
            </fieldset>
            <?= $this->StockTransactionLines->grid($fulfilment, $badges, $lineGrid) ?>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
