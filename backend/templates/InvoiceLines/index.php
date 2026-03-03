<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\InvoiceLine> $invoiceLines
 */
?>
<div class="invoiceLines index content">
    <?= $this->Html->link(__('New Invoice Line'), ['action' => 'add'], ['class' => 'button float-right']) ?>
    <h3><?= __('Invoice Lines') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('id') ?></th>
                    <th><?= $this->Paginator->sort('invoice_id') ?></th>
                    <th><?= $this->Paginator->sort('badge_id') ?></th>
                    <th><?= $this->Paginator->sort('description') ?></th>
                    <th><?= $this->Paginator->sort('quantity') ?></th>
                    <th><?= $this->Paginator->sort('unit_price') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($invoiceLines as $invoiceLine): ?>
                <tr>
                    <td><?= h($invoiceLine->id) ?></td>
                    <td><?= $invoiceLine->hasValue('invoice') ? $this->Html->link($invoiceLine->invoice->invoice_number, ['controller' => 'Invoices', 'action' => 'view', $invoiceLine->invoice->id]) : '' ?></td>
                    <td><?= $invoiceLine->hasValue('badge') ? $this->Html->link($invoiceLine->badge->badge_name, ['controller' => 'Badges', 'action' => 'view', $invoiceLine->badge->id]) : '' ?></td>
                    <td><?= h($invoiceLine->description) ?></td>
                    <td><?= $this->Number->format($invoiceLine->quantity) ?></td>
                    <td><?= $this->Number->format($invoiceLine->unit_price) ?></td>
                    <td class="actions">
                        <?= $this->Html->link(__('View'), ['action' => 'view', $invoiceLine->id]) ?>
                        <?= $this->Html->link(__('Edit'), ['action' => 'edit', $invoiceLine->id]) ?>
                        <?= $this->Form->postLink(
                            __('Delete'),
                            ['action' => 'delete', $invoiceLine->id],
                            [
                                'method' => 'delete',
                                'confirm' => __('Are you sure you want to delete # {0}?', $invoiceLine->id),
                            ]
                        ) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="paginator">
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