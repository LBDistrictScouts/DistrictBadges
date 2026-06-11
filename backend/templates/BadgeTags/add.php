<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\BadgeTag $badgeTag
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('List Badge Tags'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="badgeTags form content">
            <?= $this->Form->create($badgeTag) ?>
            <fieldset>
                <legend><?= __('Add Badge Tag') ?></legend>
                <?php
                    echo $this->Form->control('tag_name');
                    echo $this->Form->control('tag_search_text');
                    echo $this->Form->control('tag_category');
                    echo $this->Form->control('tag_order', ['default' => 0]);
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
