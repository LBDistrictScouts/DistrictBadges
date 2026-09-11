<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\User $user
 */
$address = array_filter([
    $user->address_line_1,
    $user->address_line_2,
    $user->town,
    $user->county,
    $user->postcode,
], static fn($line): bool => trim((string)$line) !== '');
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('Edit User'), ['action' => 'edit', $user->id], ['class' => 'side-nav-item']) ?>
            <?= $this->Form->postLink(__('Delete User'), ['action' => 'delete', $user->id], ['confirm' => __('Are you sure you want to delete this user?'), 'class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('List Users'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('New User'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="users view content">
            <?= $this->element('non_district_email_alert', [
                'email' => $user->email,
                'isNonDistrictEmail' => $user->non_district_email,
            ]) ?>

            <header class="user-view-heading">
                <div>
                    <p class="user-view-eyebrow"><?= __('User') ?></p>
                    <h3><?= h($user->full_name) ?></h3>
                    <p><?= h($user->email) ?></p>
                </div>
                <?= $this->Html->link(__('Edit User'), ['action' => 'edit', $user->id], ['class' => 'button button-outline']) ?>
            </header>

            <dl class="user-detail-grid user-detail-grid--single" aria-label="<?= __('User details') ?>">
                <div class="user-detail-item">
                    <dt><?= __('Account') ?></dt>
                    <dd><?= $user->hasValue('account') ? $this->Html->link($user->account->account_name, ['controller' => 'Accounts', 'action' => 'view', $user->account->id]) : __('Not assigned') ?></dd>
                </div>
            </dl>

            <?php if ($address !== []) : ?>
                <section class="related user-view-section">
                    <div class="user-section-heading"><h4><?= __('Address') ?></h4></div>
                    <address class="user-address">
                        <?php foreach ($address as $line) : ?>
                            <?= h($line) ?><br>
                        <?php endforeach; ?>
                    </address>
                </section>
            <?php endif; ?>

            <section class="related user-view-section">
                <div class="user-section-heading"><h4><?= __('Orders') ?></h4><span><?= __('{0} total', count($user->orders)) ?></span></div>
                <?php if (empty($user->orders)) : ?>
                    <p class="user-empty-state"><?= __('This user has not placed any orders.') ?></p>
                <?php else : ?>
                    <div class="table-responsive">
                        <table>
                            <thead><tr>
                                <th><?= __('Order') ?></th>
                                <th><?= __('Placed') ?></th>
                                <th><?= __('Status') ?></th>
                                <th><?= __('Ordered') ?></th>
                                <th><?= __('Fulfilled') ?></th>
                            </tr></thead>
                            <tbody>
                            <?php foreach ($user->orders as $order) : ?>
                            <tr>
                                <td><?= $this->Html->link($order->order_number, ['controller' => 'Orders', 'action' => 'view', $order->id]) ?></td>
                                <td><?= h($order->placed_date?->i18nFormat('dd MMM yyyy')) ?></td>
                                <td><?= h($order->status->label()) ?></td>
                                <td><?= $this->Number->format($order->total_ordered_quantity) ?> · <?= $this->Number->currency($order->total_ordered_amount) ?></td>
                                <td><?= $this->Number->format($order->total_fulfilled_quantity) ?> · <?= $this->Number->currency($order->total_fulfilled_amount) ?></td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>

            <section class="related user-view-section">
                <div class="user-section-heading"><h4><?= __('Fulfilments') ?></h4><span><?= __('{0} total', count($user->fulfilments)) ?></span></div>
                <?php if (empty($user->fulfilments)) : ?>
                    <p class="user-empty-state"><?= __('This user has no fulfilments.') ?></p>
                <?php else : ?>
                    <div class="table-responsive">
                        <table>
                            <thead><tr>
                                <th><?= __('Fulfilment') ?></th>
                                <th><?= __('Created') ?></th>
                                <th><?= __('Status') ?></th>
                                <th><?= __('Quantity') ?></th>
                                <th><?= __('Amount') ?></th>
                            </tr></thead>
                            <tbody>
                            <?php foreach ($user->fulfilments as $fulfilment) : ?>
                            <tr>
                            <td><?= $this->Html->link(
                                $fulfilment->fulfilment_number,
                                ['controller' => 'Fulfilments', 'action' => 'view', $fulfilment->id],
                            ) ?></td>
                            <td><?= h($fulfilment->fulfilment_date?->i18nFormat('dd MMM yyyy')) ?></td>
                            <td><?= h($fulfilment->status->label()) ?></td>
                            <td><?= $this->Number->format($fulfilment->total_quantity) ?></td>
                            <td><?= $this->Number->currency($fulfilment->total_amount) ?></td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </div>
</div>
