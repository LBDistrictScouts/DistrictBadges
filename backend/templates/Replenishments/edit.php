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
            <?= $this->Html->link(
                __('View Replenishment'),
                ['action' => 'view', $replenishment->id],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->Html->link(
                __('List Replenishments'),
                ['action' => 'index'],
                ['class' => 'side-nav-item'],
            ) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="replenishments form content">
            <?= $this->Form->create($replenishment) ?>
            <fieldset>
                <legend><?= __('Edit Wholesaler Order Number') ?></legend>
                <?= $this->Form->control('wholesaler_order_number', [
                    'label' => __('Wholesaler Order Number'),
                ]) ?>
            </fieldset>
            <?= $this->Form->button(__('Save')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
