<?php
/** @var \App\View\AppView $this @var \App\Model\Entity\Account $account */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('Edit Account'), ['action' => 'edit', $account->id], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('New Order'), ['controller' => 'Orders', 'action' => 'add'], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('Generate Invoice'), ['controller' => 'Invoices', 'action' => 'add'], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('All Accounts'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->Form->postLink(__('Delete Account'), ['action' => 'delete', $account->id], [
                'confirm' => __('Are you sure you want to delete this account?'),
                'class' => 'side-nav-item',
            ]) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="accounts view content">
            <header class="account-view-heading">
                <div>
                    <p class="account-view-eyebrow"><?= __('Account') ?></p>
                    <h3><?= h($account->account_name) ?></h3>
                    <p><?= __('Group') ?>:
                        <?= $account->hasValue('group')
                            ? $this->Html->link($account->group->group_name, [
                                'controller' => 'Groups', 'action' => 'view', $account->group->id,
                            ])
                            : __('Not assigned') ?></p>
                </div>
                <?= $this->Html->link(__('Edit Account'), ['action' => 'edit', $account->id], [
                    'class' => 'button button-outline',
                ]) ?>
            </header>

            <dl class="account-summary" aria-label="<?= __('Account summary') ?>">
                <div><dt><?= __('Sections') ?></dt><dd><?= count($account->sections) ?></dd></div>
                <div><dt><?= __('Users') ?></dt><dd><?= count($account->users) ?></dd></div>
                <div><dt><?= __('Orders') ?></dt><dd><?= count($account->orders) ?></dd></div>
                <div><dt><?= __('Invoices') ?></dt><dd><?= count($account->invoices) ?></dd></div>
            </dl>

            <section class="related account-view-section">
                <div class="account-section-heading"><h4><?= __('Sections') ?></h4><span><?= __('{0} assigned', count($account->sections)) ?></span></div>
                <?php if (empty($account->sections)) : ?>
                    <p class="account-empty-state"><?= __('No sections are assigned to this account.') ?></p>
                <?php else : ?>
                    <div class="table-responsive"><table>
                        <thead><tr><th><?= __('Section') ?></th><th><?= __('Type') ?></th><th><?= __('Meeting') ?></th></tr></thead>
                        <tbody><?php foreach ($account->sections as $section) : ?><tr>
                            <td><strong><?= h($section->section_name) ?></strong></td>
                            <td><?= h(ucfirst($section->section_type)) ?></td>
                            <td><?= h($section->meeting_day ?? __('Not recorded')) ?><?php if ($section->meeting_start_time || $section->meeting_end_time) : ?> · <?= h(implode('–', array_filter([$section->meeting_start_time, $section->meeting_end_time]))) ?><?php endif; ?></td>
                        </tr><?php endforeach; ?></tbody>
                    </table></div>
                <?php endif; ?>
            </section>

            <section class="related account-view-section">
                <div class="account-section-heading"><h4><?= __('Users') ?></h4><?= $this->Html->link(__('Manage Users'), ['controller' => 'Users', 'action' => 'index']) ?></div>
                <?php if (empty($account->users)) : ?>
                    <p class="account-empty-state"><?= __('No users belong to this account.') ?></p>
                <?php else : ?>
                    <div class="table-responsive"><table>
                        <thead><tr><th><?= __('Name') ?></th><th><?= __('Email') ?></th><th><?= __('Access') ?></th></tr></thead>
                        <tbody><?php foreach ($account->users as $user) : ?><tr>
                            <td><?= $this->Html->link($user->full_name, ['controller' => 'Users', 'action' => 'view', $user->id]) ?></td>
                            <td><?= $this->Text->autoLinkEmails(h($user->email)) ?></td>
                            <td><?= $user->can_login ? __('Enabled') : __('Disabled') ?></td>
                        </tr><?php endforeach; ?></tbody>
                    </table></div>
                <?php endif; ?>
            </section>

            <section class="related account-view-section">
                <div class="account-section-heading"><h4><?= __('Invoices') ?></h4><?= $this->Html->link(__('Generate Invoice'), ['controller' => 'Invoices', 'action' => 'add']) ?></div>
                <?php if (empty($account->invoices)) : ?>
                    <p class="account-empty-state"><?= __('No invoices have been generated for this account.') ?></p>
                <?php else : ?>
                    <div class="table-responsive"><table>
                        <thead><tr><th><?= __('Invoice') ?></th><th><?= __('Period') ?></th><th><?= __('Issued') ?></th><th><?= __('Due') ?></th><th><?= __('Total') ?></th></tr></thead>
                        <tbody><?php foreach ($account->invoices as $invoice) : ?>
                            <tr>
                                <td><?= $this->Html->link($invoice->invoice_number, ['controller' => 'Invoices', 'action' => 'view', $invoice->id]) ?></td>
                                <td><?= $invoice->period_start_date && $invoice->period_end_date ? h($invoice->period_start_date) . ' – ' . h($invoice->period_end_date) : __('Not recorded') ?></td>
                                <td><?= h($invoice->invoice_date?->i18nFormat('dd MMM yyyy')) ?></td>
                                <td><?= h($invoice->due_date?->i18nFormat('dd MMM yyyy')) ?></td>
                                <td><?= $this->Number->currency($invoice->total_amount) ?></td>
                            </tr>
                        <?php endforeach; ?></tbody>
                    </table></div>
                <?php endif; ?>
            </section>

            <section class="related account-view-section">
                <div class="account-section-heading"><h4><?= __('Orders') ?></h4><?= $this->Html->link(__('View All Orders'), ['controller' => 'Orders', 'action' => 'index']) ?></div>
                <?php if (empty($account->orders)) : ?>
                    <p class="account-empty-state"><?= __('No orders have been placed for this account.') ?></p>
                <?php else : ?>
                    <div class="table-responsive"><table>
                        <thead><tr><th><?= __('Order') ?></th><th><?= __('Ordered For') ?></th><th><?= __('Placed') ?></th><th><?= __('Status') ?></th><th><?= __('Ordered') ?></th><th><?= __('Fulfilled') ?></th></tr></thead>
                        <tbody><?php foreach ($account->orders as $order) : ?><tr>
                            <td><?= $this->Html->link($order->order_number, ['controller' => 'Orders', 'action' => 'view', $order->id]) ?></td>
                            <td><?= $order->hasValue('user') ? h($order->user->full_name) : __('Unknown user') ?><?= $order->hasValue('section') ? ' · ' . h($order->section->section_name) : '' ?></td>
                            <td><?= h($order->placed_date?->i18nFormat('dd MMM yyyy')) ?></td>
                            <td><?= h($order->status->label()) ?></td>
                            <td><?= $this->Number->format($order->total_ordered_quantity) ?> · <?= $this->Number->currency($order->total_ordered_amount) ?></td>
                            <td><?= $this->Number->format($order->total_fulfilled_quantity) ?> · <?= $this->Number->currency($order->total_fulfilled_amount) ?></td>
                        </tr><?php endforeach; ?></tbody>
                    </table></div>
                <?php endif; ?>
            </section>
        </div>
    </div>
</div>
