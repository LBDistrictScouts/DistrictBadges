<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Badge> $badges
 */
?>
<div class="badges index content">
    <?= $this->Html->link(__('New Badge'), ['action' => 'add'], ['class' => 'button float-right']) ?>
    <h3><?= __('Badges') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('id') ?></th>
                    <th><?= $this->Paginator->sort('badge_name') ?></th>
                    <th><?= $this->Paginator->sort('national_product_code') ?></th>
                    <th><?= $this->Paginator->sort('stocked') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($badges as $badge): ?>
                <tr>
                    <td>
                        <?php if (!is_null($badge->image_large_url)) : ?>
                            <img src="<?= $badge->image_large_url ?>" alt="<?= $badge->badge_name ?>" width="100" height="100" style="max-width: 100px;" />
                        <?php endif; ?>
                    </td>
                    <td><?= h($badge->badge_name) ?></td>
                    <td><?= $badge->national_product_code === null ? '' : h((string)$badge->national_product_code) ?></td>
                    <td><?= $badge->stocked ? __('Yes') : __('No') ?></td>
                    <td class="actions">
                        <?= $this->Html->link(__('View'), ['action' => 'view', $badge->id]) ?>
                        <?= $this->Html->link(__('Edit'), ['action' => 'edit', $badge->id]) ?>
                        <?= $this->Form->postLink(
                            __('Delete'),
                            ['action' => 'delete', $badge->id],
                            [
                                'method' => 'delete',
                                'confirm' => __('Are you sure you want to delete this badge?'),
                            ]
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
        <p><?= $this->Paginator->counter(__('Page {{page}} of {{pages}}, showing {{current}} record(s) out of {{count}} total')) ?></p>
    </div>
</div>
