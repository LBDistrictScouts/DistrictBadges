<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Fulfilment> $fulfilments
 */
?>
<div class="fulfilments index content">
    <?= $this->Html->link(__('New Fulfilment'), ['action' => 'add'], ['class' => 'button float-right']) ?>
    <h3><?= __('Fulfilments') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('id') ?></th>
                    <th><?= $this->Paginator->sort('fulfilment_date') ?></th>
                    <th><?= $this->Paginator->sort('fulfilment_number') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($fulfilments as $fulfilment): ?>
                <tr>
                    <td><?= h($fulfilment->id) ?></td>
                    <td><?= h($fulfilment->fulfilment_date) ?></td>
                    <td><?= h($fulfilment->fulfilment_number) ?></td>
                    <td class="actions">
                        <?= $this->Html->link(__('View'), ['action' => 'view', $fulfilment->id]) ?>
                        <?= $this->Html->link(__('Edit'), ['action' => 'edit', $fulfilment->id]) ?>
                        <?= $this->Form->postLink(
                            __('Delete'),
                            ['action' => 'delete', $fulfilment->id],
                            [
                                'method' => 'delete',
                                'confirm' => __('Are you sure you want to delete # {0}?', $fulfilment->id),
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