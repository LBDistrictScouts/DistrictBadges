<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Replenishment> $replenishments
 * @var array<string, string> $filters
 * @var array<int, string> $statusOptions
 */
?>
<div class="replenishments index content">
    <?= $this->Html->link(__('New Replenishment'), ['action' => 'add'], ['class' => 'button float-right']) ?>
    <h3><?= __('Replenishments') ?></h3>
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
    <div class="index-filters__actions">
        <?= $this->Form->button(__('Filter')) ?>
        <?= $this->Html->link(__('Clear'), ['action' => 'index'], ['class' => 'button button-outline']) ?>
    </div>
    <?= $this->Form->end() ?>
    <div class="table-responsive">
        <table class="replenishments-index-table">
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('wholesale_order_number', __('Replenishment')) ?></th>
                    <th><?= $this->Paginator->sort('wholesaler_order_number', __('Wholesaler Order')) ?></th>
                    <th><?= $this->Paginator->sort('created_date', __('Created')) ?></th>
                    <th><?= $this->Paginator->sort('status') ?></th>
                    <th><?= $this->Paginator->sort('total_ordered_quantity', __('Ord. Qty')) ?></th>
                    <th><?= $this->Paginator->sort('total_ordered_amount', __('Ord. Value')) ?></th>
                    <th><?= $this->Paginator->sort('total_received_quantity', __('Rec. Qty')) ?></th>
                    <th><?= $this->Paginator->sort('total_received_amount', __('Rec. Value')) ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($replenishments as $replenishment) : ?>
                <tr>
                    <td>
                        <?= $this->Html->link(
                            $replenishment->wholesale_order_number,
                            ['action' => 'view', $replenishment->id],
                        ) ?>
                    </td>
                    <td><?= h($replenishment->wholesaler_order_number) ?></td>
                    <td><?= h($replenishment->created_date?->i18nFormat('dd MMM yyyy')) ?></td>
                    <td><?= h($replenishment->status->label()) ?></td>
                    <td><?= $this->Number->format($replenishment->total_ordered_quantity) ?></td>
                    <td><?= $this->Number->currency($replenishment->total_ordered_amount) ?></td>
                    <td><?= $this->Number->format($replenishment->total_received_quantity) ?></td>
                    <td><?= $this->Number->currency($replenishment->total_received_amount) ?></td>
                    <td class="actions">
                        <?= $this->Html->link(__('View'), ['action' => 'view', $replenishment->id]) ?>
                        <?php if (!$replenishment->received) : ?>
                        <?= $this->Html->link(__('Edit'), ['action' => 'edit', $replenishment->id]) ?>
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
                            __('Receive'),
                            ['action' => 'receive', $replenishment->id],
                        ) ?>
                        <?php endif; ?>
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
