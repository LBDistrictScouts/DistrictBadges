<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Audit $audit
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('List Audits'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="audits form content">
            <?= $this->Form->create($audit) ?>
            <fieldset>
                <legend><?= __('Begin Audit') ?></legend>
                <p><?= __('This audit will be recorded against your logged-in user account.') ?></p>
            </fieldset>
            <?= $this->Form->button(__('Begin audit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
