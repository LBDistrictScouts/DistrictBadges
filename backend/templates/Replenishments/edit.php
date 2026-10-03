<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Replenishment $replenishment
 * @var bool $isReceived
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
                <legend><?= $isReceived ? __('Record Actual Postage Cost') : __('Edit Replenishment Details') ?></legend>
                <?php if (!$isReceived) : ?>
                <?= $this->Form->control('wholesaler_order_number', [
                    'label' => __('Wholesaler Order Number'),
                ]) ?>
                <?php endif; ?>
                <?= $this->Form->control('actual_postage_cost', [
                    'label' => __('Actual Postage Cost (GBP)'),
                    'type' => 'number',
                    'min' => 0,
                    'step' => '0.01',
                ]) ?>
            </fieldset>
            <?= $this->Form->button(__('Save')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
