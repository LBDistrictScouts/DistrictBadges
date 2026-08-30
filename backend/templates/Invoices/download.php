<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Invoice> $invoices
 * @var bool $hideDownloaded
 */
?>
<div class="invoices download content">
    <?= $this->Html->link(__('Back to Invoices'), ['action' => 'index'], ['class' => 'button float-right']) ?>
    <h3><?= __('Download Invoices') ?></h3>
    <p><?= __('Select invoices to download as invoice-generator JSON files in a ZIP archive.') ?></p>
    <button type="button" id="select-all-invoices" class="button button-outline">
        <?= __('Select All') ?>
    </button>
    <button type="button" id="toggle-downloaded" class="button button-outline">
        <?= $hideDownloaded
            ? __('Show Previously Downloaded Invoices')
            : __('Hide Previously Downloaded Invoices') ?>
    </button>
    <?= $this->Form->create(null, ['id' => 'invoice-download-form']) ?>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= __('Select') ?></th>
                    <th><?= __('Invoice') ?></th>
                    <th><?= __('Account') ?></th>
                    <th><?= __('Period') ?></th>
                    <th><?= __('Total') ?></th>
                    <th><?= __('Last Downloaded') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($invoices as $invoice) : ?>
                    <tr>
                        <td><?= $this->Form->checkbox('invoice_ids[]', [
                            'value' => $invoice->id,
                            'hiddenField' => false,
                            'label' => false,
                        ]) ?></td>
                        <td><?= $this->Html->link($invoice->invoice_number, ['action' => 'view', $invoice->id]) ?></td>
                        <td><?= h($invoice->account->account_name) ?></td>
                        <td><?= h($invoice->period_start_date) ?> – <?= h($invoice->period_end_date) ?></td>
                        <td><?= $this->Number->currency($invoice->total_amount) ?></td>
                        <td><?= $invoice->last_downloaded
                            ? h($invoice->last_downloaded->i18nFormat('dd MMM yyyy, HH:mm'))
                            : __('Never') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?= $this->Form->button(__('Download Selected')) ?>
    <?= $this->Form->end() ?>
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
<script>
document.getElementById('select-all-invoices').addEventListener('click', function (event) {
    const checkboxes = Array.from(document.querySelectorAll(
        '#invoice-download-form input[name="invoice_ids[]"]',
    ));
    const selectAll = checkboxes.some((checkbox) => !checkbox.checked);
    checkboxes.forEach((checkbox) => {
        checkbox.checked = selectAll;
    });
    event.currentTarget.textContent = selectAll ? '<?= __('Clear All') ?>' : '<?= __('Select All') ?>';
});

document.getElementById('toggle-downloaded').addEventListener('click', function () {
    const url = new URL(window.location.href);
    url.searchParams.set('hide_downloaded', '<?= $hideDownloaded ? '0' : '1' ?>');
    url.searchParams.delete('page');
    window.location.assign(url.toString());
});

document.getElementById('invoice-download-form').addEventListener('submit', async function (event) {
    event.preventDefault();
    const form = event.currentTarget;
    const button = form.querySelector('button[type="submit"]');
    button.disabled = true;

    try {
        const response = await fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            credentials: 'same-origin',
        });
        const contentType = response.headers.get('Content-Type') || '';
        if (!response.ok || !contentType.includes('application/zip')) {
            window.location.reload();
            return;
        }

        const archive = await response.blob();
        const disposition = response.headers.get('Content-Disposition') || '';
        const filenameMatch = disposition.match(/filename="?([^";]+)"?/i);
        const link = document.createElement('a');
        link.href = URL.createObjectURL(archive);
        link.download = filenameMatch ? filenameMatch[1] : 'invoices.zip';
        document.body.appendChild(link);
        link.click();
        link.remove();
        URL.revokeObjectURL(link.href);
        window.setTimeout(() => window.location.reload(), 100);
    } catch (error) {
        button.disabled = false;
        window.alert('The invoice download could not be completed. Please try again.');
    }
});
</script>
