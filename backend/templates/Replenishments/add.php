<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Replenishment $replenishment
 * @var array<string> $badges
 * @var array<string, mixed> $lineGrid
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('List Replenishments'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="replenishments form content">
            <?= $this->Form->create($replenishment) ?>
            <fieldset>
                <legend><?= __('Add Replenishment') ?></legend>
            </fieldset>
            <?= $this->StockTransactionLines->grid($replenishment, $badges, $lineGrid) ?>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
