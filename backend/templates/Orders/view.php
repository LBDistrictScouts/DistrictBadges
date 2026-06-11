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
            <?= $this->Html->link(
                __('Edit Order'),
                ['action' => 'edit', $order->id],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->Form->postLink(
                __('Delete Order'),
                ['action' => 'delete', $order->id],
                [
                    'confirm' => __('Are you sure you want to delete this order?'),
                    'class' => 'side-nav-item',
                ],
            ) ?>
            <?= $this->Html->link(__('List Orders'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('New Order'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="orders view content">
            <h3><?= h($order->order_number) ?></h3>
            <table>
                <tr>
                    <th><?= __('Status') ?></th>
                    <td><?= h($order->status->label()) ?></td>
                </tr>
                <tr>
                    <th><?= __('Account') ?></th>
                    <td>
                        <?= $order->hasValue('account')
                            ? $this->Html->link(
                                $order->account->account_name,
                                ['controller' => 'Accounts', 'action' => 'view', $order->account->id],
                            )
                            : __('No account') ?>
                    </td>
                </tr>
                <tr>
                    <th><?= __('User') ?></th>
                    <td>
                        <?= $order->hasValue('user')
                            ? $this->Html->link(
                                $order->user->full_name,
                                ['controller' => 'Users', 'action' => 'view', $order->user->id],
                            )
                            : __('No user') ?>
                    </td>
                </tr>
                <tr>
                    <th><?= __('Placed') ?></th>
                    <td><?= h($order->placed_date?->i18nFormat('dd MMM yyyy HH:mm')) ?></td>
                </tr>
                <tr>
                    <th><?= __('Ordered Total') ?></th>
                    <td>
                        <?= $this->Number->format($order->total_ordered_quantity) ?>
                        <?= __('items') ?>,
                        <?= $this->Number->currency($order->total_ordered_amount) ?>
                    </td>
                </tr>
                <tr>
                    <th><?= __('Fulfilled Total') ?></th>
                    <td>
                        <?= $this->Number->format($order->total_fulfilled_quantity) ?>
                        <?= __('items') ?>,
                        <?= $this->Number->currency($order->total_fulfilled_amount) ?>
                    </td>
                </tr>
            </table>

            <div class="related">
                <h4><?= __('Order Lines') ?></h4>
                <?php if (!empty($order->order_lines)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Badge') ?></th>
                            <th><?= __('Quantity') ?></th>
                            <th><?= __('Fulfilled') ?></th>
                            <th><?= __('Remaining') ?></th>
                            <th><?= __('Unit Price') ?></th>
                            <th><?= __('Line Amount') ?></th>
                            <th><?= __('Status') ?></th>
                        </tr>
                        <?php foreach ($order->order_lines as $line) : ?>
                        <tr>
                            <td>
                                <?= $line->hasValue('badge')
                                    ? $this->Html->link(
                                        $line->badge->badge_name,
                                        ['controller' => 'Badges', 'action' => 'view', $line->badge->id],
                                    )
                                    : __('Unknown badge') ?>
                            </td>
                            <td><?= $this->Number->format($line->quantity) ?></td>
                            <td><?= $this->Number->format($line->fulfilled_quantity) ?></td>
                            <td><?= $this->Number->format($line->remaining_quantity) ?></td>
                            <td><?= $this->Number->currency($line->unit_price) ?></td>
                            <td><?= $this->Number->currency($line->amount) ?></td>
                            <td>
                                <?php if ($line->fulfilled) : ?>
                                    <?= __('Fulfilled') ?>
                                <?php elseif ($line->fulfilled_quantity > 0) : ?>
                                    <?= __('Partially Fulfilled') ?>
                                <?php else : ?>
                                    <?= __('Open') ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                <?php else : ?>
                <p><?= __('No order lines have been added.') ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
