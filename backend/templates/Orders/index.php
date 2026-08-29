<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Order> $orders
 * @var array<string, string> $filters
 * @var array<int, string> $statusOptions
 * @var iterable<string, string> $groupOptions
 * @var iterable<string, string> $userOptions
 */
?>
<div class="orders index content">
    <?= $this->Html->link(__('New Order'), ['action' => 'add'], ['class' => 'button float-right']) ?>
    <h3><?= __('Orders') ?></h3>
    <?= $this->Form->create(null, ['type' => 'get', 'class' => 'index-filters']) ?>
    <div class="index-filters__row">
        <?= $this->Form->control('number', [
            'label' => __('Number'),
            'value' => $filters['number'],
        ]) ?>
        <?= $this->Form->control('status', [
            'label' => __('Status'),
            'options' => $statusOptions,
            'empty' => __('All statuses'),
            'value' => $filters['status'],
        ]) ?>
    </div>
    <div class="index-filters__row">
        <?= $this->Form->control('created_from', [
            'label' => __('Created From'),
            'type' => 'date',
            'value' => $filters['created_from'],
        ]) ?>
        <?= $this->Form->control('created_to', [
            'label' => __('Created To'),
            'type' => 'date',
            'value' => $filters['created_to'],
        ]) ?>
    </div>
    <div class="index-filters__row">
        <?= $this->Form->control('group_id', [
            'label' => __('Group'),
            'options' => $groupOptions,
            'empty' => __('All groups'),
            'value' => $filters['group_id'],
        ]) ?>
        <?= $this->Form->control('user_id', [
            'label' => __('User'),
            'options' => $userOptions,
            'empty' => __('All users'),
            'value' => $filters['user_id'],
        ]) ?>
    </div>
    <div class="index-filters__actions">
        <?= $this->Form->button(__('Filter')) ?>
        <?= $this->Html->link(__('Clear'), ['action' => 'index'], ['class' => 'button button-outline']) ?>
    </div>
    <?= $this->Form->end() ?>
    <div class="table-responsive">
        <table class="orders-index-table">
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('order_number', __('Order')) ?></th>
                    <th><?= $this->Paginator->sort('Groups.group_name', __('Group')) ?></th>
                    <th><?= $this->Paginator->sort('user_id', __('User')) ?></th>
                    <th><?= $this->Paginator->sort('placed_date', __('Placed')) ?></th>
                    <th><?= $this->Paginator->sort('status') ?></th>
                    <th><?= $this->Paginator->sort('total_ordered_quantity', __('Ord. Qty')) ?></th>
                    <th><?= $this->Paginator->sort('total_ordered_amount', __('Ord. Value')) ?></th>
                    <th><?= $this->Paginator->sort('total_fulfilled_quantity', __('Ful. Qty')) ?></th>
                    <th><?= $this->Paginator->sort('total_fulfilled_amount', __('Ful. Value')) ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order) : ?>
                <tr>
                    <td>
                        <?= $this->Html->link(
                            $order->order_number,
                            ['action' => 'view', $order->id],
                        ) ?>
                    </td>
                    <td>
                        <?php $group = $order->account?->group; ?>
                        <?= $group !== null
                            ? $this->Html->link(
                                $this->Text->truncate($group->group_name, 8, ['ellipsis' => '…', 'exact' => true]),
                                ['controller' => 'Groups', 'action' => 'view', $group->id],
                                ['title' => $group->group_name],
                            )
                            : __('No group') ?>
                    </td>
                    <td>
                        <?= $order->hasValue('user')
                            ? $this->Html->link(
                                trim(sprintf(
                                    '%s %s',
                                    $order->contact_first_name ?? '',
                                    $order->contact_last_name ?? '',
                                )) ?: $order->user->full_name,
                                ['controller' => 'Users', 'action' => 'view', $order->user->id],
                            )
                            : __('No user') ?>
                    </td>
                    <td><?= h($order->placed_date?->i18nFormat('dd MMM yyyy')) ?></td>
                    <td><?= h($order->status->label()) ?></td>
                    <td><?= $this->Number->format($order->total_ordered_quantity) ?></td>
                    <td><?= $this->Number->currency($order->total_ordered_amount) ?></td>
                    <td><?= $this->Number->format($order->total_fulfilled_quantity) ?></td>
                    <td><?= $this->Number->currency($order->total_fulfilled_amount) ?></td>
                    <td class="actions">
                        <?= $this->Html->link(__('View'), ['action' => 'view', $order->id]) ?>
                        <?= $this->Form->postLink(
                            __('Delete'),
                            ['action' => 'delete', $order->id],
                            [
                                'method' => 'delete',
                                'confirm' => __('Are you sure you want to delete this order?'),
                            ],
                        ) ?>
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
        <p><?= $this->Paginator->counter(
            __('Page {{page}} of {{pages}}, showing {{current}} record(s) out of {{count}} total'),
        ) ?></p>
    </div>
</div>
