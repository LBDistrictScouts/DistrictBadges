<?php
use App\Model\Enum\TagCategory;

/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\BadgeTag> $badgeTags
 * @var \App\Model\Enum\TagCategory|null $category
 */
$title = match ($category) {
    TagCategory::Sections => __('Section Tags'),
    TagCategory::BadgeTypes => __('Badge Type Tags'),
    null => __('Badge Tags'),
};
?>
<div class="badgeTags index content">
    <div class="badge-tag-index-actions float-right">
        <?php if ($category !== null) : ?>
            <?= $this->Html->link(__('Show All Tags'), ['action' => 'index'], ['class' => 'button button-outline float-right']) ?>
        <?php endif; ?>
        <?= $this->Html->link(__('New Badge Tag'), ['action' => 'add'], ['class' => 'button float-right']) ?>
    </div>
    <h3><?= h($title) ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('tag_name') ?></th>
                    <th><?= $this->Paginator->sort('tag_search_text') ?></th>
                    <th><?= $this->Paginator->sort('tag_category') ?></th>
                    <th><?= $this->Paginator->sort('tag_order') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($badgeTags as $badgeTag) : ?>
                <tr>
                    <td><?= h($badgeTag->tag_name) ?></td>
                    <td><?= h($badgeTag->tag_search_text) ?></td>
                    <td>
                        <?php if ($badgeTag->tag_category !== null) : ?>
                            <?= $this->Html->link(
                                $badgeTag->tag_category->label(),
                                ['action' => 'index', '?' => ['category' => $badgeTag->tag_category->value]],
                            ) ?>
                        <?php endif; ?>
                    </td>
                    <td><?= $this->Number->format($badgeTag->tag_order) ?></td>
                    <td class="actions">
                        <?= $this->Html->link(__('View'), ['action' => 'view', $badgeTag->id]) ?>
                        <?= $this->Html->link(__('Edit'), ['action' => 'edit', $badgeTag->id]) ?>
                        <?= $this->Form->postLink(
                            __('Delete'),
                            ['action' => 'delete', $badgeTag->id],
                            [
                                'method' => 'delete',
                                'confirm' => __('Are you sure you want to delete # {0}?', $badgeTag->id),
                            ],
                        ) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="paginator">
        <?= $this->element('pagination_limit') ?>
        <ul class="pagination">
            <?= $this->Paginator->first('<< ' . __('first')) ?>
            <?= $this->Paginator->prev('< ' . __('previous')) ?>
            <?= $this->Paginator->numbers() ?>
            <?= $this->Paginator->next(__('next') . ' >') ?>
            <?= $this->Paginator->last(__('last') . ' >>') ?>
        </ul>
        <p>
            <?= $this->Paginator->counter(
                __('Page {{page}} of {{pages}}, showing {{current}} record(s) out of {{count}} total'),
            ) ?>
        </p>
    </div>
</div>
