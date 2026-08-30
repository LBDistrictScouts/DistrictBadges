<?php
use App\Model\Enum\BadgeStatus;

/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Badge> $badges
 * @var array<string, string> $filters
 * @var array<int, string> $statusOptions
 * @var array<string, string> $stockedOptions
 * @var array<string, string> $listedOptions
 * @var \Cake\Collection\CollectionInterface|array<string, string> $sectionTagOptions
 * @var \Cake\Collection\CollectionInterface|array<string, string> $typeTagOptions
 */
?>
<div class="badges index content">
    <?= $this->Html->link(__('New Badge'), ['action' => 'add'], ['class' => 'button float-right']) ?>
    <h3><?= __('Badges') ?></h3>
    <div class="badge-index-view-switch">
        <span aria-current="page"><?= __('Table') ?></span>
        <?= $this->Html->link(__('Cards'), [
            'action' => 'cards',
            '?' => $this->request->getQueryParams(),
        ]) ?>
        <?= $this->Html->link(__('Stock'), [
            'action' => 'stock',
            '?' => $this->request->getQueryParams(),
        ]) ?>
    </div>
    <?= $this->element('badge_filters', compact(
        'filters',
        'listedOptions',
        'sectionTagOptions',
        'statusOptions',
        'stockedOptions',
        'typeTagOptions',
    ) + ['clearAction' => 'index']) ?>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= __('Image') ?></th>
                    <th><?= $this->Paginator->sort('badge_name') ?></th>
                    <th><?= $this->Paginator->sort('status') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($badges as $badge) : ?>
                <tr>
                    <td>
                        <?php if (!is_null($badge->image_large_url)) : ?>
                            <img
                                src="<?= $badge->image_large_url ?>"
                                alt="<?= $badge->badge_name ?>"
                                width="100"
                                height="100"
                                style="max-width: 100px;"
                            />
                        <?php endif; ?>
                    </td>
                    <td><?= h($badge->badge_name) ?></td>
                    <td><?= h($badge->status->label()) ?></td>
                    <td class="actions">
                        <?= $this->Html->link(__('View'), ['action' => 'view', $badge->id]) ?>
                        <?= $this->Html->link(
                            __('Transactions'),
                            ['action' => 'stockTransactions', $badge->id],
                        ) ?>
                        <?= $this->Html->link(__('Edit'), [
                            'action' => 'edit',
                            $badge->id,
                            '?' => $badge->unlisted_badge ? ['unlisted' => 'true'] : [],
                        ]) ?>
                        <?php if ($badge->status === BadgeStatus::Unstocked) : ?>
                            <?= $this->Form->postLink(
                                __('Stock'),
                                [
                                    'action' => 'activate',
                                    $badge->id,
                                    '?' => $this->request->getQueryParams(),
                                ],
                                [
                                    'confirm' => __(
                                        'Are you sure you want to mark this badge as stocked?',
                                    ),
                                ],
                            ) ?>
                        <?php endif; ?>
                        <?php if ($badge->canBeDeleted()) : ?>
                            <?= $this->Form->postLink(
                                __('Delete'),
                                ['action' => 'delete', $badge->id],
                                [
                                    'method' => 'delete',
                                    'confirm' => __('Are you sure you want to delete this badge?'),
                                ],
                            ) ?>
                        <?php endif; ?>
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
