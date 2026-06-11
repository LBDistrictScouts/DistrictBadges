<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Badge $badge
 * @var array<string, array<string, string>> $badgeTagOptions
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('List Badges'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="badges form content">
            <?= $this->Form->create($badge) ?>
            <fieldset>
                <legend><?= __('Add Badge') ?></legend>
                <?php
                    echo $this->Form->control('badge_name');
                    echo $this->Form->control('national_product_code');
                    echo $this->Form->control('stocked');
                    echo $this->Form->control('reserve_quantity', ['min' => 0]);
                    echo $this->Form->control('price');
                    echo $this->Form->control('replenishment_price');
                    echo $this->Form->control('badge_tags._ids', [
                        'type' => 'select',
                        'multiple' => 'checkbox',
                        'options' => $badgeTagOptions,
                        'label' => __('Tags'),
                        'class' => 'badge-tag-checkboxes',
                    ]);
                    ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
