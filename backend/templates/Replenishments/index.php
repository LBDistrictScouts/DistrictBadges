<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Replenishment> $replenishments
 */
?>
<div class="replenishments index content">
    <?= $this->Html->link(__('New Replenishment'), ['action' => 'add'], ['class' => 'button float-right']) ?>
    <h3><?= __('Replenishments') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('id') ?></th>
                    <th><?= $this->Paginator->sort('created_date') ?></th>
                    <th><?= $this->Paginator->sort('order_submitted') ?></th>
                    <th><?= $this->Paginator->sort('order_submitted_date') ?></th>
                    <th><?= $this->Paginator->sort('received') ?></th>
                    <th><?= $this->Paginator->sort('received_date') ?></th>
                    <th><?= $this->Paginator->sort('total_amount') ?></th>
                    <th><?= $this->Paginator->sort('total_quantity') ?></th>
                    <th><?= $this->Paginator->sort('wholesale_order_number') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($replenishments as $replenishment): ?>
                <tr>
                    <td><?= h($replenishment->id) ?></td>
                    <td><?= h($replenishment->created_date) ?></td>
                    <td><?= h($replenishment->order_submitted) ?></td>
                    <td><?= h($replenishment->order_submitted_date) ?></td>
                    <td><?= h($replenishment->received) ?></td>
                    <td><?= h($replenishment->received_date) ?></td>
                    <td><?= $this->Number->format($replenishment->total_amount) ?></td>
                    <td><?= $this->Number->format($replenishment->total_quantity) ?></td>
                    <td><?= h($replenishment->wholesale_order_number) ?></td>
                    <td class="actions">
                        <?= $this->Html->link(__('View'), ['action' => 'view', $replenishment->id]) ?>
                        <?= $this->Html->link(__('Edit'), ['action' => 'edit', $replenishment->id]) ?>
                        <?= $this->Form->postLink(
                            __('Delete'),
                            ['action' => 'delete', $replenishment->id],
                            [
                                'method' => 'delete',
                                'confirm' => __('Are you sure you want to delete # {0}?', $replenishment->id),
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