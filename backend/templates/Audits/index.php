<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Audit> $audits
 * @var \App\Model\Entity\Audit|null $openAudit
 */
?>
<div class="audits index content">
    <?= $this->Html->link(
        $openAudit ? __('Continue Open Audit') : __('New Audit'),
        $openAudit ? ['action' => 'view', $openAudit->id] : ['action' => 'add'],
        ['class' => 'button float-right'],
    ) ?>
    <h3><?= __('Audits') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('user_id') ?></th>
                    <th><?= $this->Paginator->sort('audit_timestamp') ?></th>
                    <th><?= $this->Paginator->sort('audit_completed') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($audits as $audit) : ?>
                <tr>
                    <td><?= $audit->hasValue('user') ? $this->Html->link($audit->user->full_name, ['controller' => 'Users', 'action' => 'view', $audit->user->id]) : '' ?></td>
                    <td><?= h($audit->audit_timestamp) ?></td>
                    <td><?= $audit->audit_completed ? __('Completed') : __('Open') ?></td>
                    <td class="actions">
                        <?= $this->Html->link($audit->audit_completed ? __('View') : __('Continue'), ['action' => 'view', $audit->id]) ?>
                        <?php if (!$audit->audit_completed && empty($audit->audit_lines)) : ?><?= $this->Form->postLink(
                            __('Delete'),
                            ['action' => 'delete', $audit->id],
                            [
                                'method' => 'delete',
                                'confirm' => __('Are you sure you want to delete # {0}?', $audit->id),
                            ],
                        ) ?><?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="paginator">
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
