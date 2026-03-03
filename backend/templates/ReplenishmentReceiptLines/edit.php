<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\ReplenishmentReceiptLine $replenishmentReceiptLine
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
                ['action' => 'delete', $replenishmentReceiptLine->id],
                ['confirm' => __('Are you sure you want to delete # {0}?', $replenishmentReceiptLine->id), 'class' => 'side-nav-item']
            ) ?>
            <?= $this->Html->link(__('List Replenishment Receipt Lines'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="replenishmentReceiptLines form content">
            <?= $this->Form->create($replenishmentReceiptLine) ?>
            <fieldset>
                <legend><?= __('Edit Replenishment Receipt Line') ?></legend>
                <?php
                    echo $this->Form->control('badge_id', ['options' => $badges]);
                    echo $this->Form->control('on_hand_quantity_change');
                    echo $this->Form->control('receipted_quantity_change');
                    echo $this->Form->control('pending_quantity_change');
                    echo $this->Form->control('transaction_type', [
                        'type' => 'text',
                        'value' => TransactionType::ReplenishmentReceipt->label(),
                        'disabled' => true,
                    ]);
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
