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
            <?= $this->Form->postLink(
                __('Delete'),
                ['action' => 'delete', $badge->id],
                ['confirm' => __('Are you sure you want to delete # {0}?', $badge->id), 'class' => 'side-nav-item']
            ) ?>
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
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
