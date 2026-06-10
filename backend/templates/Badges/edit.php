<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Badge $badge
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?php if ($badge->canBeDeleted()) : ?>
                <?= $this->Form->postLink(
                    __('Delete'),
                    ['action' => 'delete', $badge->id],
                    [
                        'confirm' => __('Are you sure you want to delete # {0}?', $badge->id),
                        'class' => 'side-nav-item',
                    ],
                ) ?>
            <?php endif; ?>
            <?= $this->Html->link(__('List Badges'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="badges form content">
            <?= $this->Form->create($badge) ?>
            <fieldset>
                <legend><?= __('Edit Badge') ?></legend>
                <?php
                    echo $this->Form->control('badge_name');
                    echo $this->Form->control('national_product_code');
                    echo $this->Form->control('stocked');
                    echo $this->Form->control('reserve_quantity', ['min' => 0]);
                    echo $this->Form->control('price');
                    echo $this->Form->control('replenishment_price');
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
