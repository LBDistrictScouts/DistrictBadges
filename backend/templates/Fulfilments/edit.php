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
            <?= $this->Form->postLink(
                __('Delete'),
                ['action' => 'delete', $fulfilment->id],
                ['confirm' => __('Are you sure you want to delete # {0}?', $fulfilment->id), 'class' => 'side-nav-item']
            ) ?>
            <?= $this->Html->link(__('List Fulfilments'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="fulfilments form content">
            <?= $this->Form->create($fulfilment) ?>
            <fieldset>
                <legend><?= __('Edit Fulfilment') ?></legend>
                <?php
                    echo $this->Form->control('fulfilment_date');
                    echo $this->Form->control('fulfilment_number');
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
