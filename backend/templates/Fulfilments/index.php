<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Fulfilment> $fulfilments
 * @var array<string, string> $filters
 * @var array<int, string> $statusOptions
 */
use App\Model\Enum\FulfilmentStatus;

?>
<div class="fulfilments index content">
    <?= $this->Html->link(__('New Fulfilment'), ['action' => 'add'], ['class' => 'button float-right']) ?>
    <h3><?= __('Fulfilments') ?></h3>
    <details class="badge-index-controls" data-badge-index-controls>
    <summary><?= __('Filters') ?></summary>
    <div class="badge-index-controls__body">
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
    </div>
    </details>
    <?= $this->Html->script('badge-index-controls', ['block' => true, 'defer' => true]) ?>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('fulfilment_number', __('Fulfilment')) ?></th>
                    <th><?= $this->Paginator->sort('fulfilment_date', __('Created')) ?></th>
                    <th><?= $this->Paginator->sort('dispatched_date', __('Dispatched')) ?></th>
                    <th><?= $this->Paginator->sort('status') ?></th>
                    <th><?= $this->Paginator->sort('total_quantity', __('Total Quantity')) ?></th>
                    <th><?= $this->Paginator->sort('total_amount', __('Total Amount')) ?></th>
                    <th><?= $this->Paginator->sort('dispatch_type', __('Dispatch Type')) ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($fulfilments as $fulfilment) : ?>
                <tr>
                    <td>
                        <?= $this->Html->link(
                            $fulfilment->fulfilment_number,
                            ['action' => 'view', $fulfilment->id],
                        ) ?>
                    </td>
                    <td><?= h($fulfilment->fulfilment_date?->i18nFormat('dd MMM yyyy HH:mm')) ?></td>
                    <td>
                        <?= $fulfilment->dispatched_date
                            ? h($fulfilment->dispatched_date->i18nFormat('dd MMM yyyy HH:mm'))
                            : __('Not dispatched') ?>
                    </td>
                    <td><?= h($fulfilment->status->label()) ?></td>
                    <td><?= $this->Number->format($fulfilment->total_quantity) ?></td>
                    <td><?= $this->Number->currency($fulfilment->total_amount) ?></td>
                    <td><?= h($fulfilment->dispatch_type->label()) ?></td>
                    <td class="actions">
                        <?= $this->Html->link(__('View'), ['action' => 'view', $fulfilment->id]) ?>
                        <?php if ($fulfilment->status === FulfilmentStatus::Draft) : ?>
                            <?= $this->Form->postLink(
                                __('Dispatch'),
                                ['action' => 'dispatch', $fulfilment->id],
                                [
                                    'confirm' => __('Are you sure you want to dispatch this fulfilment?'),
                                ],
                            ) ?>
                        <?php endif; ?>
                        <?php if ($fulfilment->status !== FulfilmentStatus::Dispatched) : ?>
                            <?= $this->Form->postLink(
                                __('Delete'),
                                ['action' => 'delete', $fulfilment->id],
                                [
                                    'method' => 'delete',
                                    'confirm' => __('Are you sure you want to delete this fulfilment?'),
                                ],
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
