<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Order $order
 * @var \Cake\Collection\CollectionInterface|array<string> $groups
 * @var array<string, string> $sectionOptions
 * @var array<string, string> $sectionGroups
 * @var string $selectedGroupId
 * @var bool $canChangeOrderParty
 * @var \Cake\Collection\CollectionInterface|array<string> $users
 */
$orderLineErrorMessages = [];
$orderLineErrors = $order->getError('order_lines');
if (is_array($orderLineErrors)) {
    array_walk_recursive($orderLineErrors, static function ($message) use (&$orderLineErrorMessages): void {
        if (is_scalar($message)) {
            $orderLineErrorMessages[] = (string)$message;
        }
    });
} elseif ($orderLineErrors) {
    $orderLineErrorMessages[] = (string)$orderLineErrors;
}
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('List Orders'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="orders form content">
            <?= $this->Form->create($order) ?>
            <fieldset>
                <legend><?= __('Edit Order') ?></legend>
                <?php
                    if ($canChangeOrderParty) {
                        echo $this->Form->control('group_id', [
                            'label' => __('Group'),
                            'options' => $groups,
                            'empty' => __('Select a group'),
                            'value' => $selectedGroupId,
                            'required' => true,
                        ]);
                        echo $this->Form->control('section_id', [
                            'label' => __('Section'),
                            'options' => $sectionOptions,
                            'empty' => __('Select a section'),
                            'help' => __('Only sections belonging to the selected group are available.'),
                            'required' => true,
                        ]);
                        echo $this->Form->control('user_id', ['options' => $users]);
                    } else {
                        echo '<p><strong>' . h(__('Group')) . ':</strong> '
                            . h($order->account?->group?->group_name ?? __('Unknown group')) . '</p>';
                        echo '<p><strong>' . h(__('Section')) . ':</strong> '
                            . h($order->section?->section_name ?? __('No section')) . '</p>';
                        echo '<p><strong>' . h(__('User')) . ':</strong> '
                            . h($order->user?->full_name ?? __('Unknown user')) . '</p>';
                    }
                ?>
            </fieldset>
            <section class="related">
                <h4><?= __('Order Lines') ?></h4>
                <p><?= __('Set a line quantity to zero to cancel it. A partially fulfilled line cannot be reduced below the quantity already fulfilled.') ?></p>
                <?php if (!empty($order->order_lines)) : ?>
                    <div class="table-responsive">
                        <table class="orders-edit-lines">
                            <thead>
                                <tr>
                                    <th><?= __('Badge') ?></th>
                                    <th><?= __('Quantity') ?></th>
                                    <th><?= __('Unit Price') ?></th>
                                    <th><?= __('Line Amount') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($order->order_lines as $index => $line) : ?>
                                    <?php $unitPriceCents = (int)round((float)$line->unit_price * 100); ?>
                                    <?php $fulfilledQuantity = (int)$line->fulfilled_quantity; ?>
                                    <?php $canEditQuantity = $fulfilledQuantity === 0 || $fulfilledQuantity < (int)($line->getOriginal('quantity') ?? $line->quantity); ?>
                                    <tr>
                                        <td>
                                            <?= h($line->badge?->badge_name ?? __('Unknown badge')) ?>
                                            <?php if ($canEditQuantity) : ?>
                                                <input
                                                    type="hidden"
                                                    name="order_lines[<?= (int)$index ?>][id]"
                                                    value="<?= h($line->id) ?>"
                                                >
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($canEditQuantity) : ?>
                                                <input
                                                    type="number"
                                                    id="order-line-quantity-<?= (int)$index ?>"
                                                    name="order_lines[<?= (int)$index ?>][quantity]"
                                                    value="<?= h($line->quantity) ?>"
                                                    min="<?= $fulfilledQuantity ?>"
                                                    step="1"
                                                    required
                                                    data-order-line-quantity
                                                    data-price-cents="<?= $unitPriceCents ?>"
                                                    aria-label="<?= h(__('Quantity for {0}', $line->badge?->badge_name ?? __('Unknown badge'))) ?>"
                                                >
                                            <?php else : ?>
                                                <?= $this->Number->format($line->quantity) ?>
                                                <small><?= __('Fully fulfilled') ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= $this->Number->currency($line->unit_price) ?></td>
                                        <td>
                                            <output data-order-line-amount>
                                                <?= $this->Number->currency($line->amount) ?>
                                            </output>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else : ?>
                    <p><?= __('No order lines have been added.') ?></p>
                <?php endif; ?>
                <?php if ($orderLineErrorMessages !== []) : ?>
                    <p class="error-message">
                        <?= h(implode(' ', $orderLineErrorMessages)) ?>
                    </p>
                <?php endif; ?>
            </section>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-order-line-quantity]').forEach((input) => {
        const updateAmount = () => {
            const cents = Number(input.dataset.priceCents || 0) * Number(input.value || 0);
            const amount = input.closest('tr')?.querySelector('[data-order-line-amount]');
            if (amount) amount.textContent = `£${(cents / 100).toFixed(2)}`;
        };
        input.addEventListener('input', updateAmount);
    });
});
</script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const group = document.getElementById('group-id');
    const sections = document.getElementById('section-id');
    const sectionGroups = <?= json_encode($sectionGroups, JSON_HEX_TAG | JSON_HEX_AMP) ?>;
    if (!group || !sections) return;

    const options = Array.from(sections.options);
    const filterSections = () => {
        options.forEach((option) => {
            if (option.value === '') return;
            const visible = sectionGroups[option.value] === group.value;
            option.hidden = !visible;
            option.disabled = !visible;
            if (!visible) option.selected = false;
        });
    };
    group.addEventListener('change', filterSections);
    filterSections();
});
</script>
