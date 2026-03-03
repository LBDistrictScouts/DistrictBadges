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
            <?= $this->Html->link(__('Edit Invoice Line'), ['action' => 'edit', $invoiceLine->id], ['class' => 'side-nav-item']) ?>
            <?= $this->Form->postLink(__('Delete Invoice Line'), ['action' => 'delete', $invoiceLine->id], ['confirm' => __('Are you sure you want to delete # {0}?', $invoiceLine->id), 'class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('List Invoice Lines'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('New Invoice Line'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
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
                    <td><?= $invoiceLine->hasValue('invoice') ? $this->Html->link($invoiceLine->invoice->invoice_number, ['controller' => 'Invoices', 'action' => 'view', $invoiceLine->invoice->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Badge') ?></th>
                    <td><?= $invoiceLine->hasValue('badge') ? $this->Html->link($invoiceLine->badge->badge_name, ['controller' => 'Badges', 'action' => 'view', $invoiceLine->badge->id]) : '' ?></td>
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
                    <td><?= $this->Number->format($invoiceLine->unit_price) ?></td>
                </tr>
            </table>
        </div>
    </div>
</div>