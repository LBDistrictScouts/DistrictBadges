<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\InvoiceLine $invoiceLine
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('List Invoice Lines'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="invoiceLines view content">
            <h3><?= h($invoiceLine->description) ?></h3>
            <table>
                <tr>
                    <th><?= __('Id') ?></th>
                    <td><?= h($invoiceLine->id) ?></td>
                </tr>
                <tr>
                    <th><?= __('Invoice') ?></th>
                    <td><?= $this->Html->link($invoiceLine->invoice_summary->invoice->invoice_number, ['controller' => 'Invoices', 'action' => 'view', $invoiceLine->invoice_summary->invoice->id]) ?></td>
                </tr>
                <tr>
                    <th><?= __('Badge') ?></th>
                    <td><?= $invoiceLine->hasValue('badge') ? $this->Html->link($invoiceLine->badge->badge_name, ['controller' => 'Badges', 'action' => 'view', $invoiceLine->badge->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Order') ?></th>
                    <td><?= $this->Html->link($invoiceLine->invoice_summary->order->order_number, ['controller' => 'Orders', 'action' => 'view', $invoiceLine->invoice_summary->order->id]) ?></td>
                </tr>
                <tr>
                    <th><?= __('Ordered By') ?></th>
                    <td><?= h($invoiceLine->invoice_summary->order->user->full_name) ?></td>
                </tr>
                <tr>
                    <th><?= __('Section') ?></th>
                    <td><?= $invoiceLine->invoice_summary->order->hasValue('section') ? h($invoiceLine->invoice_summary->order->section->section_name) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Description') ?></th>
                    <td><?= h($invoiceLine->description) ?></td>
                </tr>
                <tr>
                    <th><?= __('Quantity') ?></th>
                    <td><?= $this->Number->format($invoiceLine->quantity) ?></td>
                </tr>
                <tr>
                    <th><?= __('Unit Price') ?></th>
                    <td><?= $this->Number->currency($invoiceLine->unit_price) ?></td>
                </tr>
                <tr>
                    <th><?= __('Line Amount') ?></th>
                    <td><?= $this->Number->currency($invoiceLine->line_amount) ?></td>
                </tr>
            </table>
        </div>
    </div>
</div>
