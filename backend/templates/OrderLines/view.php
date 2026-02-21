<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\OrderLine $orderLine
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('Edit Order Line'), ['action' => 'edit', $orderLine->id], ['class' => 'side-nav-item']) ?>
            <?= $this->Form->postLink(__('Delete Order Line'), ['action' => 'delete', $orderLine->id], ['confirm' => __('Are you sure you want to delete this order line?'), 'class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('List Order Lines'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('New Order Line'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="orderLines view content">
            <h3><?= __('Order Line') ?></h3>
            <table>
                <tr>
                    <th><?= __('Order') ?></th>
                    <td><?= $orderLine->hasValue('order') ? $this->Html->link($orderLine->order->order_number, ['controller' => 'Orders', 'action' => 'view', $orderLine->order->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Badge') ?></th>
                    <td><?= $orderLine->hasValue('badge') ? $this->Html->link($orderLine->badge->badge_name, ['controller' => 'Badges', 'action' => 'view', $orderLine->badge->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Quantity') ?></th>
                    <td><?= $this->Number->format($orderLine->quantity) ?></td>
                </tr>
                <tr>
                    <th><?= __('Amount') ?></th>
                    <td><?= $this->Number->format($orderLine->amount) ?></td>
                </tr>
                <tr>
                    <th><?= __('Fulfilled') ?></th>
                    <td><?= $orderLine->fulfilled ? __('Yes') : __('No'); ?></td>
                </tr>
            </table>
        </div>
    </div>
</div>
