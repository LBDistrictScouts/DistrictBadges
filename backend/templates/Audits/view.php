<?php
/** @var \App\View\AppView $this @var \App\Model\Entity\Audit $audit */
$counted = [];
foreach ($audit->audit_lines as $line) {
    $counted[(string)$line->badge_id] = $line;
}
?>
<div class="audits view content">
    <div class="audit-heading">
        <div>
            <h3><?= __('Stock audit') ?></h3>
            <p><?= h($audit->user->full_name) ?> · <?= h($audit->audit_timestamp) ?> ·
                <strong><?= $audit->audit_completed ? __('Completed') : __('Open') ?></strong></p>
        </div>
        <?= $this->Html->link(__('All audits'), ['action' => 'index'], ['class' => 'button button-outline']) ?>
    </div>

    <?php if (!$audit->audit_completed) : ?>
        <fieldset class="stock-transaction-lines audit-line-builder">
            <legend><?= __('Badges') ?></legend>
            <div class="row stock-line-builder">
                <div class="column stock-line-badge-column">
                    <label for="audit-badge-search"><?= __('Search badge') ?></label>
                    <select id="audit-badge-search">
                        <option value=""><?= __('Select a badge') ?></option>
                        <?php foreach ($badges as $badge) :
                            $existing = $counted[(string)$badge->id] ?? null;
                            if ($existing) {
                                continue;
                            }
                            $expected = (int)$badge->on_hand_quantity;
                            $actual = $expected;
                            ?>
                            <option value="<?= h($badge->id) ?>"
                                data-name="<?= h($badge->badge_name) ?>"
                                data-expected="<?= $expected ?>"
                                data-actual="<?= $actual ?>"
                                data-counted="0">
                                <?= h($badge->badge_name) ?>
                                · <?= isset($lastAudited[$badge->id])
                                    ? __('last audited {0}', $lastAudited[$badge->id]->format('j M Y'))
                                    : __('never audited') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <button type="button" class="button button-outline" id="audit-add-badge" disabled>
                <?= __('Add badge') ?>
            </button>
            <button type="button" class="button button-clear" id="audit-open-stock-dialog">
                <?= __('Stock an unstocked badge') ?>
            </button>
        </fieldset>
    <?php endif; ?>

    <h4><?= __('Counted badges ({0})', count($audit->audit_lines)) ?></h4>
    <?php if (empty($audit->audit_lines)) : ?>
        <p><?= __('No badges have been counted yet. Search for a badge above to add the first line.') ?></p>
    <?php else : ?>
        <?= $this->StockTransactionLines->auditGrid($audit) ?>
    <?php endif; ?>

    <?php if (!$audit->audit_completed) : ?>
        <?= $this->Form->postLink(__('Complete audit and apply adjustments'), ['action' => 'complete', $audit->id], [
            'class' => 'button',
            'confirm' => __('Complete this audit? Counts will be locked and stock will be adjusted.'),
        ]) ?>

        <dialog id="audit-count-dialog" class="audit-count-dialog">
            <?= $this->Form->create(null, ['url' => ['action' => 'count', $audit->id], 'id' => 'audit-count-form']) ?>
            <?= $this->Form->hidden('badge_id', ['id' => 'audit-count-badge-id']) ?>
            <div class="audit-count-dialog__heading">
                <div><span class="audit-count-dialog__eyebrow"><?= __('Count badge') ?></span>
                    <h4 id="audit-count-name"></h4></div>
                <button type="button" class="button button-clear audit-dialog-close" aria-label="<?= __('Close') ?>" id="audit-dialog-close">×</button>
            </div>
            <div class="audit-expected"><span><?= __('Expected stock') ?></span><strong id="audit-count-expected"></strong></div>
            <?= $this->Form->control('actual_quantity', [
                'type' => 'number', 'min' => 0, 'required' => true,
                'label' => __('Actual stock'), 'id' => 'audit-count-actual', 'inputmode' => 'numeric',
            ]) ?>
            <output id="audit-count-difference" class="audit-count-difference"></output>
            <div class="audit-dialog-actions">
                <button type="button" class="button button-outline" id="audit-dialog-cancel"><?= __('Cancel') ?></button>
                <?= $this->Form->button(__('Save count')) ?>
            </div>
            <?= $this->Form->end() ?>
        </dialog>

        <dialog id="audit-stock-dialog" class="audit-count-dialog">
            <?= $this->Form->create(null, ['url' => ['action' => 'stockBadge', $audit->id]]) ?>
            <div class="audit-count-dialog__heading">
                <div><span class="audit-count-dialog__eyebrow"><?= __('Badge catalogue') ?></span>
                    <h4><?= __('Stock an unstocked badge') ?></h4></div>
                <button type="button" class="button button-clear audit-dialog-close" aria-label="<?= __('Close') ?>" id="audit-stock-close">×</button>
            </div>
            <p><?= __('Find a catalogue badge to begin managing its stock and include it in this audit.') ?></p>
            <?= $this->Form->control('badge_id', [
                'options' => $unstockedBadges,
                'empty' => __('Search for an unstocked badge'),
                'label' => __('Badge'),
                'id' => 'audit-unstocked-badge',
                'required' => true,
            ]) ?>
            <div class="audit-dialog-actions">
                <button type="button" class="button button-outline" id="audit-stock-cancel"><?= __('Cancel') ?></button>
                <?= $this->Form->button(__('Stock badge'), ['id' => 'audit-stock-submit', 'disabled' => true]) ?>
            </div>
            <?= $this->Form->end() ?>
        </dialog>
    <?php endif; ?>
</div>

<?php if (!$audit->audit_completed) : ?>
<script>
(function ($) {
    var select = $('#audit-badge-search');
    var addButton = document.getElementById('audit-add-badge');
    var dialog = document.getElementById('audit-count-dialog');
    var badgeId = document.getElementById('audit-count-badge-id');
    var badgeName = document.getElementById('audit-count-name');
    var expected = document.getElementById('audit-count-expected');
    var actual = document.getElementById('audit-count-actual');
    var difference = document.getElementById('audit-count-difference');
    var stockDialog = document.getElementById('audit-stock-dialog');
    var unstocked = $('#audit-unstocked-badge');
    var stockSubmit = document.getElementById('audit-stock-submit');

    select.select2({placeholder: '<?= __('Search for a badge') ?>', width: '100%'});
    unstocked.select2({
        placeholder: '<?= __('Search for an unstocked badge') ?>',
        width: '100%',
        dropdownParent: $('#audit-stock-dialog')
    });
    select.on('change', function () { addButton.disabled = !this.value; });

    function updateDifference() {
        var value = Number(actual.value) - Number(expected.textContent);
        difference.textContent = '<?= __('Difference') ?>: ' + (value >= 0 ? '+' : '') + value;
    }
    function openCount(data) {
        badgeId.value = data.id;
        badgeName.textContent = data.name;
        expected.textContent = data.expected;
        actual.value = data.actual;
        updateDifference();
        dialog.showModal();
        window.setTimeout(function () { actual.select(); }, 0);
    }
    addButton.addEventListener('click', function () {
        var option = select.find(':selected')[0];
        if (!option || !option.value) return;
        openCount({id: option.value, name: option.dataset.name, expected: option.dataset.expected, actual: option.dataset.actual});
    });
    document.querySelectorAll('.audit-count-button').forEach(function (button) {
        button.addEventListener('click', function () { openCount({id: button.dataset.badgeId, name: button.dataset.badgeName, expected: button.dataset.expected, actual: button.dataset.actual}); });
    });
    actual.addEventListener('input', updateDifference);
    document.getElementById('audit-dialog-close').addEventListener('click', function () { dialog.close(); });
    document.getElementById('audit-dialog-cancel').addEventListener('click', function () { dialog.close(); });
    dialog.addEventListener('click', function (event) { if (event.target === dialog) dialog.close(); });
    document.getElementById('audit-open-stock-dialog').addEventListener('click', function () { stockDialog.showModal(); });
    document.getElementById('audit-stock-close').addEventListener('click', function () { stockDialog.close(); });
    document.getElementById('audit-stock-cancel').addEventListener('click', function () { stockDialog.close(); });
    unstocked.on('change', function () { stockSubmit.disabled = !this.value; });
    stockDialog.addEventListener('click', function (event) { if (event.target === stockDialog) stockDialog.close(); });
}(jQuery));
</script>
<?php endif; ?>
<script>
(function () {
    var body = document.getElementById('audit-lines-body');
    if (!body) return;
    var activeKey = 'created';
    var direction = 1;
    document.querySelectorAll('[data-audit-sort]').forEach(function (button) {
        button.addEventListener('click', function () {
            var key = button.dataset.auditSort;
            direction = activeKey === key ? direction * -1 : 1;
            activeKey = key;
            document.querySelectorAll('[data-audit-sort]').forEach(function (item) {
                item.removeAttribute('data-sort-direction');
                item.removeAttribute('aria-sort');
            });
            button.dataset.sortDirection = direction === 1 ? 'asc' : 'desc';
            button.setAttribute('aria-sort', direction === 1 ? 'ascending' : 'descending');
            var type = button.dataset.sortType;
            Array.from(body.rows).sort(function (left, right) {
                var leftValue = left.dataset[key];
                var rightValue = right.dataset[key];
                var comparison = type === 'number'
                    ? Number(leftValue) - Number(rightValue)
                    : leftValue.localeCompare(rightValue);
                if (comparison === 0) comparison = left.dataset.created.localeCompare(right.dataset.created);
                return comparison * direction;
            }).forEach(function (row) { body.appendChild(row); });
        });
    });
}());
</script>
