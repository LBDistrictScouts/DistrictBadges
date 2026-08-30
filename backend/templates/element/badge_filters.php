<?php
/**
 * @var \App\View\AppView $this
 * @var array<string, string> $filters
 * @var array<int, string> $statusOptions
 * @var array<string, string> $stockedOptions
 * @var array<string, string> $listedOptions
 * @var \Cake\Collection\CollectionInterface|array<string, string> $sectionTagOptions
 * @var \Cake\Collection\CollectionInterface|array<string, string> $typeTagOptions
 * @var string $clearAction
 * @var bool|null $showStockSort
 */
$showStockSort ??= false;
?>
<details class="badge-index-controls" data-badge-index-controls>
<summary><?= $showStockSort ? __('Filters & Sorting') : __('Filters') ?></summary>
<div class="badge-index-controls__body">
<?= $this->Form->create(null, ['type' => 'get', 'class' => 'index-filters']) ?>
<div class="index-filters__row">
    <?= $this->Form->control('name', [
        'label' => __('Name'),
        'value' => $filters['name'],
    ]) ?>
    <?= $this->Form->control('status', [
        'label' => __('Availability Status'),
        'options' => $statusOptions,
        'empty' => __('All availability statuses'),
        'value' => $filters['status'],
    ]) ?>
    <?= $this->Form->control('stocked', [
        'label' => __('Stocking'),
        'options' => $stockedOptions,
        'empty' => __('All badges'),
        'value' => $filters['stocked'],
    ]) ?>
    <?= $this->Form->control('listed', [
        'label' => __('Listing'),
        'options' => $listedOptions,
        'empty' => __('All listings'),
        'value' => $filters['listed'],
    ]) ?>
</div>
<div class="index-filters__row">
    <?= $this->Form->control('section_tag', [
        'label' => __('Section'),
        'options' => $sectionTagOptions,
        'empty' => __('All sections'),
        'value' => $filters['section_tag'],
    ]) ?>
    <?= $this->Form->control('type_tag', [
        'label' => __('Badge Type'),
        'options' => $typeTagOptions,
        'empty' => __('All badge types'),
        'value' => $filters['type_tag'],
    ]) ?>
</div>
<div class="index-filters__actions">
    <?= $this->Form->button(__('Filter')) ?>
    <?= $this->Html->link(__('Clear'), ['action' => $clearAction], ['class' => 'button button-outline']) ?>
</div>
<?= $this->Form->end() ?>
<?php if ($showStockSort) : ?>
    <?php
    $stockSortOptions = [
        'on_hand_quantity' => __('On Hand'),
        'pending_quantity' => __('Pending'),
        'reserve_quantity' => __('Reserve'),
        'receipted_quantity' => __('Receipted'),
        'fulfilled_quantity' => __('Fulfilled'),
        'invoiced_quantity' => __('Invoiced'),
    ];
    ?>
    <?= $this->Form->create(null, ['type' => 'get', 'class' => 'badge-stock-sort']) ?>
    <?php foreach ($filters as $name => $value) : ?>
        <?php if ($value !== '') : ?>
            <?= $this->Form->hidden($name, ['value' => $value]) ?>
        <?php endif; ?>
    <?php endforeach; ?>
    <?= $this->Form->control('sort', [
        'label' => __('Sort Stock By'),
        'options' => $stockSortOptions,
        'empty' => __('Choose stock value'),
        'value' => $this->request->getQuery('sort'),
    ]) ?>
    <?= $this->Form->control('direction', [
        'label' => __('Direction'),
        'options' => ['desc' => __('Descending'), 'asc' => __('Ascending')],
        'value' => $this->request->getQuery('direction', 'desc'),
    ]) ?>
    <?= $this->Form->button(__('Sort')) ?>
    <?= $this->Form->end() ?>
<?php endif; ?>
</div>
</details>
<?= $this->Html->script('badge-index-controls', ['block' => true, 'defer' => true]) ?>
