<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Account $account
 * @var \Cake\Collection\CollectionInterface|string[] $groups
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('List Accounts'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="accounts form content">
            <?= $this->Form->create($account) ?>
            <fieldset>
                <legend><?= __('Add Account') ?></legend>
                <?php
                    echo $this->Form->control('account_name');
                    echo $this->Form->control('group_id', ['options' => $groups]);
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
