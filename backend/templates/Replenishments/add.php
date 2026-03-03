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
            <?= $this->Html->link(__('List Replenishments'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="replenishments form content">
            <?= $this->Form->create($replenishment) ?>
            <fieldset>
                <legend><?= __('Add Replenishment') ?></legend>
                <?php
                    echo $this->Form->control('created_date');
                    echo $this->Form->control('order_submitted');
                    echo $this->Form->control('order_submitted_date');
                    echo $this->Form->control('received');
                    echo $this->Form->control('received_date');
                    echo $this->Form->control('total_amount');
                    echo $this->Form->control('total_quantity');
                    echo $this->Form->control('wholesale_order_number');
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
