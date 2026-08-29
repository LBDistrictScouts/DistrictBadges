<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Fulfilment $fulfilment
 * @var array<string> $badges
 * @var array<string, mixed> $lineGrid
 */
use App\Model\Enum\DispatchType;

$dispatchTypeOptions = [];
foreach (DispatchType::cases() as $dispatchType) {
    $dispatchTypeOptions[$dispatchType->value] = $dispatchType->label();
}
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('List Fulfilments'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="fulfilments form content">
            <?= $this->Form->create($fulfilment) ?>
            <fieldset>
                <legend><?= __('Add Fulfilment') ?></legend>
                <?= $this->Form->control('dispatch_type', [
                    'options' => $dispatchTypeOptions,
                    'empty' => __('Add an order to set the dispatch type'),
                    'value' => $this->getRequest()->is('post') ? $fulfilment->dispatch_type : '',
                    'data-dispatch-type' => true,
                ]) ?>
                <div data-dispatch-address hidden>
                    <strong><?= __('Dispatch Address') ?></strong>
                    <p data-dispatch-address-lines></p>
                </div>
            </fieldset>
            <?= $this->StockTransactionLines->grid($fulfilment, $badges, $lineGrid) ?>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
<script>
(function () {
    var type = document.querySelector('[data-dispatch-type]');
    var address = document.querySelector('[data-dispatch-address]');
    var addressLines = document.querySelector('[data-dispatch-address-lines]');
    var grid = document.querySelector('[data-stock-transaction-lines]');
    if (!type || !address || !addressLines || !grid) return;

    var overridden = false;
    var collectionType = '<?= DispatchType::ShopCollection->value ?>';
    var latestAddress = [];
    var renderAddress = function () {
        address.hidden = type.value === '' || type.value === collectionType || latestAddress.length === 0;
        addressLines.replaceChildren();
        latestAddress.forEach(function (line, index) {
            if (index > 0) addressLines.appendChild(document.createElement('br'));
            addressLines.appendChild(document.createTextNode(line));
        });
    };
    type.addEventListener('change', function () {
        overridden = true;
        renderAddress();
    });
    grid.addEventListener('stock-lines:bulk-loaded', function (event) {
        latestAddress = Array.isArray(event.detail.dispatch_address)
            ? event.detail.dispatch_address
            : [];
        if (!overridden) type.value = String(event.detail.dispatch_type);
        renderAddress();
    });
    renderAddress();
})();
</script>
