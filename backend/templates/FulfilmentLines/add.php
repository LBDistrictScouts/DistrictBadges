<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\FulfilmentLine $fulfilmentLine
 * @var \Cake\Collection\CollectionInterface|array<string> $badges
 * @var \Cake\Collection\CollectionInterface|array<string> $fulfilments
 * @var \Cake\Collection\CollectionInterface|array<string> $audits
 * @var \Cake\Collection\CollectionInterface|array<string> $replenishments
 * @var array<string, string> $orderLines
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
                    echo $this->Form->control('order_line_id', [
                        'options' => $orderLines,
                        'empty' => __('Select an order line'),
                    ]);
                    echo $this->Form->control('on_hand_quantity_change');
                    echo $this->Form->control('receipted_quantity_change');
                    echo $this->Form->control('pending_quantity_change');
                    echo $this->Form->control('fulfilled_quantity_change');
                    echo $this->Form->control('unit_price');
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
