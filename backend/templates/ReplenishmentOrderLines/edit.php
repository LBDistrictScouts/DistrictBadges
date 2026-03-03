<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\ReplenishmentOrderLine $replenishmentOrderLine
 * @var string[]|\Cake\Collection\CollectionInterface $badges
 * @var string[]|\Cake\Collection\CollectionInterface $fulfilments
 * @var string[]|\Cake\Collection\CollectionInterface $audits
 * @var string[]|\Cake\Collection\CollectionInterface $replenishments
 */
use App\Model\Enum\TransactionType;
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Form->postLink(
                __('Delete'),
                ['action' => 'delete', $replenishmentOrderLine->id],
                ['confirm' => __('Are you sure you want to delete # {0}?', $replenishmentOrderLine->id), 'class' => 'side-nav-item']
            ) ?>
            <?= $this->Html->link(__('List Replenishment Order Lines'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="replenishmentOrderLines form content">
            <?= $this->Form->create($replenishmentOrderLine) ?>
            <fieldset>
                <legend><?= __('Edit Replenishment Order Line') ?></legend>
                <?php
                    echo $this->Form->control('badge_id', ['options' => $badges]);
                    echo $this->Form->control('on_hand_quantity_change');
                    echo $this->Form->control('receipted_quantity_change');
                    echo $this->Form->control('pending_quantity_change');
                    echo $this->Form->control('transaction_type', [
                        'type' => 'text',
                        'value' => TransactionType::ReplenishmentOrder->label(),
                        'disabled' => true,
                    ]);
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
