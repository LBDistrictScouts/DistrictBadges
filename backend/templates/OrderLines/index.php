<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\OrderLine> $orderLines
 */
?>
<div class="orderLines index content">
    <?= $this->Html->link(__('New Order Line'), ['action' => 'add'], ['class' => 'button float-right']) ?>
    <h3><?= __('Order Lines') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('order_id', __('Order')) ?></th>
                    <th><?= $this->Paginator->sort('badge_id', __('Badge')) ?></th>
                    <th><?= $this->Paginator->sort('quantity') ?></th>
                    <th><?= $this->Paginator->sort('amount') ?></th>
                    <th><?= $this->Paginator->sort('fulfilled') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orderLines as $orderLine): ?>
                <tr>
                    <td><?= $orderLine->hasValue('order') ? $this->Html->link($orderLine->order->order_number, ['controller' => 'Orders', 'action' => 'view', $orderLine->order->id]) : '' ?></td>
                    <td><?= $orderLine->hasValue('badge') ? $this->Html->link($orderLine->badge->badge_name, ['controller' => 'Badges', 'action' => 'view', $orderLine->badge->id]) : '' ?></td>
                    <td><?= $this->Number->format($orderLine->quantity) ?></td>
                    <td><?= $this->Number->format($orderLine->amount) ?></td>
                    <td><?= h($orderLine->fulfilled) ?></td>
                    <td class="actions">
                        <?= $this->Html->link(__('View'), ['action' => 'view', $orderLine->id]) ?>
                        <?= $this->Html->link(__('Edit'), ['action' => 'edit', $orderLine->id]) ?>
                        <?= $this->Form->postLink(
                            __('Delete'),
                            ['action' => 'delete', $orderLine->id],
                            [
                                'method' => 'delete',
                                'confirm' => __('Are you sure you want to delete this order line?'),
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
