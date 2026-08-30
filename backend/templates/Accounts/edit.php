<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Account $account
 * @var \Cake\Collection\CollectionInterface|array<string> $groups
 * @var array<string, string> $sectionOptions
 * @var array<string, string> $sectionGroups
 * @var list<string> $selectedSectionIds
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Form->postLink(
                __('Delete'),
                ['action' => 'delete', $account->id],
                ['confirm' => __('Are you sure you want to delete # {0}?', $account->id), 'class' => 'side-nav-item'],
            ) ?>
            <?= $this->Html->link(__('List Accounts'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="accounts form content">
            <?= $this->Form->create($account) ?>
            <fieldset>
                <legend><?= __('Edit Account') ?></legend>
                <?php
                    echo $this->Form->control('account_name');
                    echo $this->Form->control('group_id', ['options' => $groups]);
                    echo $this->Form->control('section_ids', [
                        'label' => __('Sections'),
                        'options' => $sectionOptions,
                        'multiple' => true,
                        'value' => $selectedSectionIds,
                        'help' => __('Only sections in the selected group are shown.'),
                    ]);
                    ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const group = document.getElementById('group-id');
    const sections = document.getElementById('section-ids');
    const sectionGroups = <?= json_encode($sectionGroups, JSON_HEX_TAG | JSON_HEX_AMP) ?>;
    if (!group || !sections) return;

    const options = Array.from(sections.options);
    const filterSections = () => {
        options.forEach((option) => {
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
