<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Invoice> $invoices
 * @var string $invoiceGenerationMonth
 * @var array<string, string> $filters
 * @var \Cake\Collection\CollectionInterface|array<string, string> $accountOptions
 */
?>
<div class="invoices index content">
    <?= $this->Html->link(__('Generate Invoice'), ['action' => 'add'], ['class' => 'button float-right']) ?>
    <?= $this->Form->postLink(__('Run Monthly Invoice Generation'), ['action' => 'runMonthly'], [
        'class' => 'button float-right',
        'style' => 'margin-right: 1rem',
        'confirm' => __('Do you want to generate all invoices for {0}?', $invoiceGenerationMonth),
    ]) ?>
    <?= $this->Html->link(__('Download Invoices'), ['action' => 'download'], [
        'class' => 'button float-right',
        'style' => 'margin-right: 1rem',
    ]) ?>
    <h3><?= __('Invoices') ?></h3>
    <details class="badge-index-controls" data-badge-index-controls>
    <summary><?= __('Filters') ?></summary>
    <div class="badge-index-controls__body">
        <?= $this->Form->create(null, ['type' => 'get', 'class' => 'index-filters']) ?>
        <div class="index-filters__row">
            <?= $this->Form->control('number', [
                'label' => __('Invoice Number'),
                'value' => $filters['number'],
            ]) ?>
            <?= $this->Form->control('account_id', [
                'label' => __('Account'),
                'options' => $accountOptions,
                'empty' => __('All accounts'),
                'value' => $filters['account_id'],
            ]) ?>
            <?= $this->Form->control('invoice_from', [
                'label' => __('Invoice From'),
                'type' => 'date',
                'value' => $filters['invoice_from'],
            ]) ?>
            <?= $this->Form->control('invoice_to', [
                'label' => __('Invoice To'),
                'type' => 'date',
                'value' => $filters['invoice_to'],
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
                    <th><?= $this->Paginator->sort('invoice_date') ?></th>
                    <th><?= $this->Paginator->sort('period_start_date', __('Period Start')) ?></th>
                    <th><?= $this->Paginator->sort('period_end_date', __('Period End')) ?></th>
                    <th><?= $this->Paginator->sort('invoice_number') ?></th>
                    <th><?= $this->Paginator->sort('account_id', __('Account')) ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($invoices as $invoice) : ?>
                <tr>
                    <td><?= h($invoice->invoice_date) ?></td>
                    <td><?= h($invoice->period_start_date) ?></td>
                    <td><?= h($invoice->period_end_date) ?></td>
                    <td><?= h($invoice->invoice_number) ?></td>
                    <td><?= $invoice->hasValue('account') ? $this->Html->link($invoice->account->account_name, ['controller' => 'Accounts', 'action' => 'view', $invoice->account->id]) : '' ?></td>
                    <td class="actions">
                        <?= $this->Html->link(__('View'), ['action' => 'view', $invoice->id]) ?>
                        <?= $this->Html->link(__('Edit'), ['action' => 'edit', $invoice->id]) ?>
                        <?= $this->Form->postLink(
                            __('Delete'),
                            ['action' => 'delete', $invoice->id],
                            [
                                'method' => 'delete',
                                'confirm' => __('Are you sure you want to delete this invoice?'),
                            ],
                        ) ?>
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
        <p><?= $this->Paginator->counter(__('Page {{page}} of {{pages}}, showing {{current}} record(s) out of {{count}} total')) ?></p>
    </div>
</div>
