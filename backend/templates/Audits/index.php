<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Audit> $audits
 */
?>
<div class="audits index content">
    <?= $this->Html->link(__('New Audit'), ['action' => 'add'], ['class' => 'button float-right']) ?>
    <h3><?= __('Audits') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('id') ?></th>
                    <th><?= $this->Paginator->sort('user_id') ?></th>
                    <th><?= $this->Paginator->sort('audit_timestamp') ?></th>
                    <th><?= $this->Paginator->sort('audit_completed') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($audits as $audit): ?>
                <tr>
                    <td><?= h($audit->id) ?></td>
                    <td><?= $audit->hasValue('user') ? $this->Html->link($audit->user->first_name, ['controller' => 'Users', 'action' => 'view', $audit->user->id]) : '' ?></td>
                    <td><?= h($audit->audit_timestamp) ?></td>
                    <td><?= h($audit->audit_completed) ?></td>
                    <td class="actions">
                        <?= $this->Html->link(__('View'), ['action' => 'view', $audit->id]) ?>
                        <?= $this->Html->link(__('Edit'), ['action' => 'edit', $audit->id]) ?>
                        <?= $this->Form->postLink(
                            __('Delete'),
                            ['action' => 'delete', $audit->id],
                            [
                                'method' => 'delete',
                                'confirm' => __('Are you sure you want to delete # {0}?', $audit->id),
                            ]
                        ) ?>
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