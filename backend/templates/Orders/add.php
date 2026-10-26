<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Order $order
 * @var \Cake\Collection\CollectionInterface|array<string> $groups
 * @var array<string, string> $sectionOptions
 * @var array<string, string> $sectionGroups
 * @var string $selectedGroupId
 * @var \Cake\Collection\CollectionInterface|array<string> $users
 * @var array<string> $badges
 * @var array<string, mixed> $lineGrid
 */
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
                <legend><?= __('Add Order') ?></legend>
                <?php
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
                ?>
            </fieldset>
            <?= $this->StockTransactionLines->grid($order, $badges, $lineGrid) ?>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
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
