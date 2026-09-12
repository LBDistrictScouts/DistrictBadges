<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Fulfilment $fulfilment
 * @var \App\Model\Entity\User|null $user
 * @var \App\Model\Entity\Account|null $account
 * @var \App\Model\Entity\Group|null $group
 */
$dispatchAddress = array_filter([
    $fulfilment->dispatch_address_line_1,
    $fulfilment->dispatch_address_line_2,
    $fulfilment->dispatch_town,
    $fulfilment->dispatch_county,
    $fulfilment->dispatch_postcode,
], static fn($line): bool => trim((string)$line) !== '');
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?php if ($fulfilment->status === \App\Model\Enum\FulfilmentStatus::Draft) : ?>
            <?= $this->Form->postLink(
                __('Dispatch Fulfilment'),
                ['action' => 'dispatch', $fulfilment->id],
                [
                    'confirm' => __('Are you sure you want to dispatch this fulfilment?'),
                    'class' => 'side-nav-item',
                ],
            ) ?>
            <?php endif; ?>
            <?= $this->Html->link(
                __('List Fulfilments'),
                ['action' => 'index'],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->Html->link(
                __('New Fulfilment'),
                ['action' => 'add'],
                ['class' => 'side-nav-item'],
            ) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="fulfilments view content">
            <h3><?= h($fulfilment->fulfilment_number) ?></h3>
            <div class="fulfilment-overview">
                <section>
                    <h4><?= __('Fulfilment') ?></h4>
                    <dl>
                        <div>
                            <dt><?= __('Status') ?></dt>
                            <dd><?= h($fulfilment->status->label()) ?></dd>
                        </div>
                        <div>
                            <dt><?= __('Total Quantity') ?></dt>
                            <dd><?= $this->Number->format($fulfilment->total_quantity) ?></dd>
                        </div>
                        <div>
                            <dt><?= __('Total Amount') ?></dt>
                            <dd><?= $this->Number->currency($fulfilment->total_amount) ?></dd>
                        </div>
                    </dl>
                </section>
                <section>
                    <h4><?= __('Customer') ?></h4>
                    <dl>
                        <div>
                            <dt><?= __('User') ?></dt>
                            <dd>
                        <?= $user === null
                            ? __('Unknown user')
                            : $this->Html->link(
                                $user->full_name,
                                ['controller' => 'Users', 'action' => 'view', $user->id],
                            ) ?>
                        <?php if ($user?->email) : ?>
                            <br><?= h($user->email) ?>
                        <?php endif; ?>
                            </dd>
                        </div>
                        <div>
                            <dt><?= __('Group') ?></dt>
                            <dd>
                        <?= $group === null
                            ? __('Unknown group')
                            : $this->Html->link(
                                $group->group_name,
                                ['controller' => 'Groups', 'action' => 'view', $group->id],
                            ) ?>
                            </dd>
                        </div>
                        <div>
                            <dt><?= __('Account') ?></dt>
                            <dd>
                        <?= $account === null
                            ? __('Unknown account')
                            : $this->Html->link(
                                $account->account_name,
                                ['controller' => 'Accounts', 'action' => 'view', $account->id],
                            ) ?>
                            </dd>
                        </div>
                    </dl>
                </section>
                <section>
                    <h4><?= __('Dispatch') ?></h4>
                    <dl>
                        <div>
                            <dt><?= __('Dispatch Type') ?></dt>
                            <dd><?= h($fulfilment->dispatch_type->label()) ?></dd>
                        </div>
                        <div>
                            <dt><?= __('Postage Charge') ?></dt>
                            <dd><?= $this->Number->currency($fulfilment->postage_charge) ?></dd>
                        </div>
                        <div>
                            <dt><?= __('Dispatch Address') ?></dt>
                            <dd>
                                <?= $dispatchAddress === []
                                    ? __('Collection')
                                    : implode('<br>', array_map('h', $dispatchAddress)) ?>
                            </dd>
                        </div>
                    </dl>
                </section>
                <section>
                    <h4><?= __('Activity') ?></h4>
                    <dl>
                        <div>
                            <dt><?= __('Created') ?></dt>
                            <dd><?= h($fulfilment->fulfilment_date?->i18nFormat('dd MMM yyyy HH:mm')) ?></dd>
                        </div>
                        <div>
                            <dt><?= __('Dispatched') ?></dt>
                            <dd>
                        <?= $fulfilment->dispatched_date
                            ? h($fulfilment->dispatched_date->i18nFormat('dd MMM yyyy HH:mm'))
                            : __('Not dispatched') ?>
                            </dd>
                        </div>
                        <div>
                            <dt><?= __('Last Dispatch Email Sent') ?></dt>
                            <dd>
                        <?= $fulfilment->last_notification_sent_at
                            ? h($fulfilment->last_notification_sent_at->i18nFormat('dd MMM yyyy HH:mm'))
                            : __('Not sent') ?>
                        <?php if ($fulfilment->status === \App\Model\Enum\FulfilmentStatus::Dispatched) : ?>
                        <?= $this->Form->postLink(
                            __('Resend Dispatch Email'),
                            ['action' => 'resendNotification', $fulfilment->id],
                            [
                                'confirm' => __('Resend the dispatch notification email?'),
                                'class' => 'button button-outline float-right',
                            ],
                        ) ?>
                        <?php endif; ?>
                            </dd>
                        </div>
                    </dl>
                </section>
            </div>

            <div class="related">
                <h4><?= __('Fulfilment Lines') ?></h4>
                <?php if (!empty($fulfilment->fulfilment_lines)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Badge') ?></th>
                            <th><?= __('Quantity') ?></th>
                            <th><?= __('Unit Price') ?></th>
                            <th><?= __('Line Amount') ?></th>
                            <th><?= __('Processed') ?></th>
                        </tr>
                        <?php foreach ($fulfilment->fulfilment_lines as $line) : ?>
                        <tr>
                            <td>
                                <?= $line->hasValue('badge')
                                    ? $this->Html->link(
                                        $line->badge->badge_name,
                                        ['controller' => 'Badges', 'action' => 'view', $line->badge->id],
                                    )
                                    : __('Unknown badge') ?>
                            </td>
                            <td><?= $this->Number->format($line->fulfilled_quantity_change) ?></td>
                            <td>
                                <?= $line->unit_price === null
                                    ? ''
                                    : $this->Number->currency($line->unit_price) ?>
                            </td>
                            <td>
                                <?= $line->monetary_amount === null
                                    ? ''
                                    : $this->Number->currency($line->monetary_amount) ?>
                            </td>
                            <td><?= h($line->transaction_timestamp?->i18nFormat('dd MMM yyyy HH:mm')) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                <?php else : ?>
                <p><?= __('No fulfilment lines have been added.') ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
