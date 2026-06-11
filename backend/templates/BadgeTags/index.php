<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\BadgeTag> $badgeTags
 */
?>
<div class="badgeTags index content">
    <?= $this->Html->link(__('New Badge Tag'), ['action' => 'add'], ['class' => 'button float-right']) ?>
    <h3><?= __('Badge Tags') ?></h3>
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
                    <td><?= $badgeTag->tag_category === null ? '' : h($badgeTag->tag_category->label()) ?></td>
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
