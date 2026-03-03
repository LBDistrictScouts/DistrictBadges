<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Audit $audit
 * @var string[]|\Cake\Collection\CollectionInterface $users
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Form->postLink(
                __('Delete'),
                ['action' => 'delete', $audit->id],
                ['confirm' => __('Are you sure you want to delete # {0}?', $audit->id), 'class' => 'side-nav-item']
            ) ?>
            <?= $this->Html->link(__('List Audits'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="audits form content">
            <?= $this->Form->create($audit) ?>
            <fieldset>
                <legend><?= __('Edit Audit') ?></legend>
                <?php
                    echo $this->Form->control('user_id', ['options' => $users]);
                    echo $this->Form->control('audit_timestamp', [
                        'type' => 'text',
                        'value' => $audit->get('audit_timestamp')
                            ? (string)$audit->get('audit_timestamp')
                            : '',
                        'disabled' => true,
                    ]);
                    echo $this->Form->control('audit_completed');
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
