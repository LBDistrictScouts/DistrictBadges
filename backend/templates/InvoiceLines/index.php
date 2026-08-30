<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\InvoiceLine> $invoiceLines
 */
?>
<div class="invoiceLines index content">
    <h3><?= __('Invoice Lines') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= __('Invoice') ?></th>
                    <th><?= $this->Paginator->sort('badge_id') ?></th>
                    <th><?= __('Order') ?></th>
                    <th><?= $this->Paginator->sort('description') ?></th>
                    <th><?= $this->Paginator->sort('quantity') ?></th>
                    <th><?= $this->Paginator->sort('unit_price') ?></th>
                    <th><?= $this->Paginator->sort('line_amount') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($invoiceLines as $invoiceLine) : ?>
                <tr>
                    <td><?= $this->Html->link($invoiceLine->invoice_summary->invoice->invoice_number, ['controller' => 'Invoices', 'action' => 'view', $invoiceLine->invoice_summary->invoice->id]) ?></td>
                    <td><?= $invoiceLine->hasValue('badge') ? $this->Html->link($invoiceLine->badge->badge_name, ['controller' => 'Badges', 'action' => 'view', $invoiceLine->badge->id]) : '' ?></td>
                    <td><?= $this->Html->link($invoiceLine->invoice_summary->order->order_number, ['controller' => 'Orders', 'action' => 'view', $invoiceLine->invoice_summary->order->id]) ?></td>
                    <td><?= h($invoiceLine->description) ?></td>
                    <td><?= $this->Number->format($invoiceLine->quantity) ?></td>
                    <td><?= $this->Number->currency($invoiceLine->unit_price) ?></td>
                    <td><?= $this->Number->currency($invoiceLine->line_amount) ?></td>
                    <td class="actions">
                        <?= $this->Html->link(__('View'), ['action' => 'view', $invoiceLine->id]) ?>
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
