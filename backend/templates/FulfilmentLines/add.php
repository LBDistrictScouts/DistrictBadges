<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\FulfilmentLine $fulfilmentLine
 * @var \Cake\Collection\CollectionInterface|string[] $badges
 * @var \Cake\Collection\CollectionInterface|string[] $fulfilments
 * @var \Cake\Collection\CollectionInterface|string[] $audits
 * @var \Cake\Collection\CollectionInterface|string[] $replenishments
 */
use App\Model\Enum\TransactionType;
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('List Fulfilment Lines'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="fulfilmentLines form content">
            <?= $this->Form->create($fulfilmentLine) ?>
            <fieldset>
                <legend><?= __('Add Fulfilment Line') ?></legend>
                <?php
                    echo $this->Form->control('badge_id', ['options' => $badges]);
                    echo $this->Form->control('on_hand_quantity_change');
                    echo $this->Form->control('receipted_quantity_change');
                    echo $this->Form->control('pending_quantity_change');
                    echo $this->Form->control('transaction_type', [
                        'type' => 'text',
                        'value' => TransactionType::Fulfilment->label(),
                        'disabled' => true,
                    ]);
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
