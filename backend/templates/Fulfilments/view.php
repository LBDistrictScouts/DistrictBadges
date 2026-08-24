<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Fulfilment $fulfilment
 */
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
            <table>
                <tr>
                    <th><?= __('Status') ?></th>
                    <td><?= h($fulfilment->status->label()) ?></td>
                </tr>
                <tr>
                    <th><?= __('Created') ?></th>
                    <td><?= h($fulfilment->fulfilment_date?->i18nFormat('dd MMM yyyy HH:mm')) ?></td>
                </tr>
                <tr>
                    <th><?= __('Dispatched') ?></th>
                    <td>
                        <?= $fulfilment->dispatched_date
                            ? h($fulfilment->dispatched_date->i18nFormat('dd MMM yyyy HH:mm'))
                            : __('Not dispatched') ?>
                    </td>
                </tr>
                <tr>
                    <th><?= __('Last Dispatch Email Sent') ?></th>
                    <td>
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
                    </td>
                </tr>
                <tr>
                    <th><?= __('Total Quantity') ?></th>
                    <td><?= $this->Number->format($fulfilment->total_quantity) ?></td>
                </tr>
                <tr>
                    <th><?= __('Total Amount') ?></th>
                    <td><?= $this->Number->currency($fulfilment->total_amount) ?></td>
                </tr>
            </table>

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
