<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Replenishment $replenishment
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?php if (!$replenishment->received) : ?>
            <?= $this->Html->link(
                __('Edit Wholesaler Order Number'),
                ['action' => 'edit', $replenishment->id],
                ['class' => 'side-nav-item'],
            ) ?>
            <?php endif; ?>
            <?php if (!in_array(
                $replenishment->status,
                [
                    \App\Model\Enum\ReplenishmentStatus::Received,
                    \App\Model\Enum\ReplenishmentStatus::Cancelled,
                ],
                true,
            )) : ?>
            <?= $this->Html->link(
                __('Receive Replenishment'),
                ['action' => 'receive', $replenishment->id],
                ['class' => 'side-nav-item'],
            ) ?>
            <?php endif; ?>
            <?php if (!$replenishment->received) : ?>
                <?= $this->Form->postLink(
                    __('Delete Replenishment'),
                    ['action' => 'delete', $replenishment->id],
                    [
                        'confirm' => __('Are you sure you want to delete this replenishment?'),
                        'class' => 'side-nav-item',
                    ],
                ) ?>
            <?php endif; ?>
            <?= $this->Html->link(
                __('List Replenishments'),
                ['action' => 'index'],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->Html->link(
                __('New Replenishment'),
                ['action' => 'add'],
                ['class' => 'side-nav-item'],
            ) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="replenishments view content">
            <h3><?= h($replenishment->replenishment_number) ?></h3>
            <table>
                <tr>
                    <th><?= __('Wholesaler Order Number') ?></th>
                    <td><?= h($replenishment->wholesaler_order_number) ?></td>
                </tr>
                <tr>
                    <th><?= __('Status') ?></th>
                    <td><?= h($replenishment->status->label()) ?></td>
                </tr>
                <tr>
                    <th><?= __('Created') ?></th>
                    <td><?= h($replenishment->created_date?->i18nFormat('dd MMM yyyy HH:mm')) ?></td>
                </tr>
                <tr>
                    <th><?= __('Submitted') ?></th>
                    <td>
                        <?= $replenishment->order_submitted_date
                            ? h($replenishment->order_submitted_date->i18nFormat('dd MMM yyyy HH:mm'))
                            : __('Not yet submitted') ?>
                    </td>
                </tr>
                <tr>
                    <th><?= __('Received') ?></th>
                    <td>
                        <?= $replenishment->received_date
                            ? h($replenishment->received_date->i18nFormat('dd MMM yyyy HH:mm'))
                            : __('Not yet received') ?>
                    </td>
                </tr>
                <tr>
                    <th><?= __('Ordered Total') ?></th>
                    <td>
                        <?= $this->Number->format($replenishment->total_ordered_quantity) ?>
                        <?= __('items') ?>,
                        <?= $this->Number->currency($replenishment->total_ordered_amount) ?>
                    </td>
                </tr>
                <tr>
                    <th><?= __('Received Total') ?></th>
                    <td>
                        <?= $this->Number->format($replenishment->total_received_quantity) ?>
                        <?= __('items') ?>,
                        <?= $this->Number->currency($replenishment->total_received_amount) ?>
                    </td>
                </tr>
            </table>

            <div class="related">
                <h4><?= __('Ordered Lines') ?></h4>
                <?php if (!empty($replenishment->replenishment_order_lines)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Badge') ?></th>
                            <th><?= __('Quantity') ?></th>
                            <th><?= __('Unit Price') ?></th>
                            <th><?= __('Line Amount') ?></th>
                        </tr>
                        <?php foreach ($replenishment->replenishment_order_lines as $line) : ?>
                        <tr>
                            <td>
                                <?= $line->hasValue('badge')
                                    ? $this->Html->link(
                                        $line->badge->badge_name,
                                        ['controller' => 'Badges', 'action' => 'view', $line->badge->id],
                                    )
                                    : __('Unknown badge') ?>
                            </td>
                            <td><?= $this->Number->format($line->pending_quantity_change) ?></td>
                            <td><?= $this->Number->currency($line->unit_price ?? 0) ?></td>
                            <td><?= $this->Number->currency($line->monetary_amount ?? 0) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                <?php else : ?>
                <p><?= __('No ordered lines have been added.') ?></p>
                <?php endif; ?>
            </div>

            <div class="related">
                <h4><?= __('Received Lines') ?></h4>
                <?php if (!empty($replenishment->replenishment_receipt_lines)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Badge') ?></th>
                            <th><?= __('Quantity') ?></th>
                            <th><?= __('Unit Price') ?></th>
                            <th><?= __('Line Amount') ?></th>
                        </tr>
                        <?php foreach ($replenishment->replenishment_receipt_lines as $line) : ?>
                        <tr>
                            <td>
                                <?= $line->hasValue('badge')
                                    ? $this->Html->link(
                                        $line->badge->badge_name,
                                        ['controller' => 'Badges', 'action' => 'view', $line->badge->id],
                                    )
                                    : __('Unknown badge') ?>
                            </td>
                            <td><?= $this->Number->format($line->receipted_quantity_change) ?></td>
                            <td><?= $this->Number->currency($line->unit_price ?? 0) ?></td>
                            <td><?= $this->Number->currency($line->monetary_amount ?? 0) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                <?php else : ?>
                <p><?= __('No received lines have been added.') ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
