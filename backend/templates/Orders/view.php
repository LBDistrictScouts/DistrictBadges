<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Order $order
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('Edit Order'), ['action' => 'edit', $order->id], ['class' => 'side-nav-item']) ?>
            <?= $this->Form->postLink(__('Delete Order'), ['action' => 'delete', $order->id], ['confirm' => __('Are you sure you want to delete # {0}?', $order->id), 'class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('List Orders'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('New Order'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="orders view content">
            <h3><?= h($order->order_number) ?></h3>
            <table>
                <tr>
                    <th><?= __('Id') ?></th>
                    <td><?= h($order->id) ?></td>
                </tr>
                <tr>
                    <th><?= __('Order Number') ?></th>
                    <td><?= h($order->order_number) ?></td>
                </tr>
                <tr>
                    <th><?= __('Amount') ?></th>
                    <td><?= h($order->amount) ?></td>
                </tr>
                <tr>
                    <th><?= __('Account') ?></th>
                    <td><?= $order->hasValue('account') ? $this->Html->link($order->account->account_name, ['controller' => 'Accounts', 'action' => 'view', $order->account->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Total Quantity') ?></th>
                    <td><?= $this->Number->format($order->total_quantity) ?></td>
                </tr>
                <tr>
                    <th><?= __('Placed Date') ?></th>
                    <td><?= h($order->placed_date) ?></td>
                </tr>
                <tr>
                    <th><?= __('Fulfilled') ?></th>
                    <td><?= $order->fulfilled ? __('Yes') : __('No'); ?></td>
                </tr>
            </table>
        </div>
    </div>
</div>