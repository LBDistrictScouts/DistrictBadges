<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Audit> $audits
 * @var \App\Model\Entity\Audit|null $openAudit
 * @var array<string, string> $filters
 * @var array<string, string> $completionOptions
 * @var \Cake\Collection\CollectionInterface|array<string, string> $userOptions
 */
?>
<div class="audits index content">
    <?= $this->Html->link(
        $openAudit ? __('Continue Open Audit') : __('New Audit'),
        $openAudit ? ['action' => 'view', $openAudit->id] : ['action' => 'add'],
        ['class' => 'button float-right'],
    ) ?>
    <h3><?= __('Audits') ?></h3>
    <details class="badge-index-controls" data-badge-index-controls>
    <summary><?= __('Filters') ?></summary>
    <div class="badge-index-controls__body">
        <?= $this->Form->create(null, ['type' => 'get', 'class' => 'index-filters']) ?>
        <div class="index-filters__row">
            <?= $this->Form->control('number', [
                'label' => __('Audit Number'),
                'value' => $filters['number'],
            ]) ?>
            <?= $this->Form->control('user_id', [
                'label' => __('User'),
                'options' => $userOptions,
                'empty' => __('All users'),
                'value' => $filters['user_id'],
            ]) ?>
            <?= $this->Form->control('completed', [
                'label' => __('Status'),
                'options' => $completionOptions,
                'empty' => __('All statuses'),
                'value' => $filters['completed'],
            ]) ?>
            <?= $this->Form->control('audited_from', [
                'label' => __('Audited From'),
                'type' => 'date',
                'value' => $filters['audited_from'],
            ]) ?>
            <?= $this->Form->control('audited_to', [
                'label' => __('Audited To'),
                'type' => 'date',
                'value' => $filters['audited_to'],
            ]) ?>
        </div>
        <div class="index-filters__actions">
            <?= $this->Form->button(__('Filter')) ?>
            <?= $this->Html->link(__('Clear'), ['action' => 'index'], ['class' => 'button button-outline']) ?>
        </div>
        <?= $this->Form->end() ?>
    </div>
    </details>
    <?= $this->Html->script('badge-index-controls', ['block' => true, 'defer' => true]) ?>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('audit_number', __('Audit Number')) ?></th>
                    <th><?= $this->Paginator->sort('user_id') ?></th>
                    <th><?= $this->Paginator->sort('audit_timestamp') ?></th>
                    <th><?= $this->Paginator->sort('audit_completed') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($audits as $audit) : ?>
                <tr>
                    <td><?= $this->Html->link($audit->audit_number, ['action' => 'view', $audit->id]) ?></td>
                    <td><?= $audit->hasValue('user') ? $this->Html->link($audit->user->full_name, ['controller' => 'Users', 'action' => 'view', $audit->user->id]) : '' ?></td>
                    <td><?= h($audit->audit_timestamp) ?></td>
                    <td><?= $audit->audit_completed ? __('Completed') : __('Open') ?></td>
                    <td class="actions">
                        <?= $this->Html->link($audit->audit_completed ? __('View') : __('Continue'), ['action' => 'view', $audit->id]) ?>
                        <?php if (!$audit->audit_completed && empty($audit->audit_lines)) : ?>
                            <?= $this->Form->postLink(
                                __('Delete'),
                                ['action' => 'delete', $audit->id],
                                [
                                    'method' => 'delete',
                                    'confirm' => __('Are you sure you want to delete # {0}?', $audit->id),
                                ],
                            ) ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="paginator">
        <?= $this->element('pagination_limit') ?>
        <ul class="pagination">
            <?= $this->Paginator->first('<< ' . __('first')) ?>
            <?= $this->Paginator->prev('< ' . __('previous')) ?>
            <?= $this->Paginator->numbers() ?>
            <?= $this->Paginator->next(__('next') . ' >') ?>
            <?= $this->Paginator->last(__('last') . ' >>') ?>
        </ul>
        <p><?= $this->Paginator->counter(__('Page {{page}} of {{pages}}, showing {{current}} record(s) out of {{count}} total')) ?></p>
    </div>
</div>
