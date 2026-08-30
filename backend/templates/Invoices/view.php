<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Invoice $invoice
 * @var bool $showAllDetails
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('Edit Invoice'), ['action' => 'edit', $invoice->id], ['class' => 'side-nav-item']) ?>
            <?= $this->Form->postLink(__('Delete Invoice'), ['action' => 'delete', $invoice->id], ['confirm' => __('Are you sure you want to delete this invoice?'), 'class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('List Invoices'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('Download Invoices'), ['action' => 'download'], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('Generate Invoice'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="invoices view content">
            <h3><?= h($invoice->invoice_number) ?></h3>
            <table>
                <tr>
                    <th><?= __('Invoice Number') ?></th>
                    <td><?= h($invoice->invoice_number) ?></td>
                </tr>
                <tr>
                    <th><?= __('Account') ?></th>
                    <td><?= $invoice->hasValue('account') ? $this->Html->link($invoice->account->account_name, ['controller' => 'Accounts', 'action' => 'view', $invoice->account->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Invoice Date') ?></th>
                    <td><?= h($invoice->invoice_date) ?></td>
                </tr>
                <tr>
                    <th><?= __('Due Date') ?></th>
                    <td><?= h($invoice->due_date) ?></td>
                </tr>
                <tr>
                    <th><?= __('Billing Period') ?></th>
                    <td>
                        <?php if ($invoice->period_start_date && $invoice->period_end_date) : ?>
                            <?= h($invoice->period_start_date) ?> – <?= h($invoice->period_end_date) ?>
                        <?php else : ?>
                            <?= __('Not recorded') ?>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>

            <div class="related">
                <div class="related-heading invoice-summary-heading">
                    <h4><?= __('Invoice Summary') ?></h4>
                    <?= $this->Html->link(
                        $showAllDetails ? __('Hide all details') : __('Show all details'),
                        ['action' => 'view', $invoice->id, '?' => ['show_details' => $showAllDetails ? '0' : '1']],
                        ['class' => 'button'],
                    ) ?>
                </div>
                <?php if (empty($invoice->invoice_summaries)) : ?>
                    <p><?= __('No order fulfilments have been added to this invoice yet.') ?></p>
                <?php else : ?>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th><?= __('Order') ?></th>
                                    <th><?= __('Fulfilment') ?></th>
                                    <th><?= __('Ordered For') ?></th>
                                    <th><?= __('Quantity') ?></th>
                                    <th><?= __('Amount') ?></th>
                                    <th><?= __('Reconciliation') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($invoice->invoice_summaries as $summary) : ?>
                                    <tr>
                                        <td><?= $this->Html->link($summary->order->order_number, ['controller' => 'Orders', 'action' => 'view', $summary->order->id]) ?></td>
                                        <td><?= $this->Html->link($summary->fulfilment->fulfilment_number, ['controller' => 'Fulfilments', 'action' => 'view', $summary->fulfilment->id]) ?></td>
                                        <td>
                                            <span><?= h($summary->order->user->full_name) ?></span>
                                                <?php if ($summary->order->hasValue('section')) : ?>
                                                    <br><span><?= h($summary->order->section->section_name) ?></span>
                                                <?php endif; ?>
                                        </td>
                                        <td><?= $this->Number->format($summary->quantity) ?></td>
                                        <td><?= $this->Number->currency($summary->line_amount) ?></td>
                                        <td>
                                            <button type="button" class="button button-outline invoice-reconciliation-toggle"
                                                    data-summary-id="<?= h($summary->id) ?>">
                                                <?= $showAllDetails ? __('Hide details') : __('Show details') ?>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php foreach ($summary->invoice_lines as $invoiceLine) : ?>
                                        <tr class="invoice-reconciliation-row"
                                            data-summary-id="<?= h($summary->id) ?>" <?= $showAllDetails ? '' : 'hidden' ?>>
                                            <td colspan="3">
                                                <strong><?= h($invoiceLine->description) ?></strong>
                                                <span><?= $this->Number->currency($invoiceLine->unit_price) ?> <?= __('each') ?></span>
                                            </td>
                                            <td><?= $this->Number->format($invoiceLine->quantity) ?></td>
                                            <td><?= $this->Number->currency($invoiceLine->line_amount) ?></td>
                                            <td><?= __('Badge detail') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="4"><?= __('Total') ?></th>
                                    <th><?= $this->Number->currency($invoice->total_amount) ?></th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<script>
document.querySelectorAll('.invoice-reconciliation-toggle').forEach((button) => {
    button.addEventListener('click', () => {
        const rows = document.querySelectorAll(
            `.invoice-reconciliation-row[data-summary-id="${button.dataset.summaryId}"]`,
        );
        const show = Array.from(rows).some((row) => row.hidden);
        rows.forEach((row) => {
            row.hidden = !show;
        });
        button.textContent = show
            ? '<?= __('Hide details') ?>'
            : '<?= __('Show details') ?>';
    });
});
</script>
