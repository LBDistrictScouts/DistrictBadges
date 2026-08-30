<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Invoice $invoice
 * @var \Cake\Collection\CollectionInterface|string[] $accounts
 * @var string $yesterday
 * @var array<string, string> $accountStartDates
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('List Invoices'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="invoices form content">
            <?= $this->Form->create($invoice) ?>
            <fieldset>
                <legend><?= __('Generate Invoice') ?></legend>
                <?php
                    echo $this->Form->control('start_date', [
                        'type' => 'date',
                        'required' => true,
                        'max' => $yesterday,
                        'label' => __('Fulfilments from'),
                    ]);
                    echo $this->Form->control('end_date', [
                        'type' => 'date',
                        'required' => true,
                        'max' => $yesterday,
                        'label' => __('Fulfilments to'),
                    ]);
                    echo $this->Form->control('account_id', ['options' => $accounts]);
                ?>
            </fieldset>
            <p><?= __('The invoice will include dispatched badges from completed days in this date range and will be due in 30 days.') ?></p>
            <?= $this->Form->button(__('Generate Invoice')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
<script>
(function () {
    var startDates = <?= json_encode($accountStartDates, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    var account = document.getElementById('account-id');
    var start = document.getElementById('start-date');
    if (!account || !start) return;

    function setAccountStartDate() {
        start.value = startDates[account.value] || '2026-01-01';
    }

    account.addEventListener('change', setAccountStartDate);
    if (!start.value) setAccountStartDate();
}());
</script>
