<?php
use App\Model\Enum\BadgeStatus;

/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Badge> $badges
 */
?>
<div class="badges index content badge-card-index">
    <?= $this->Html->link(__('New Badge'), ['action' => 'add'], ['class' => 'button float-right']) ?>
    <h3><?= __('Badges') ?></h3>
    <div class="badge-index-view-switch">
        <?= $this->Html->link(__('Table'), [
            'action' => 'table',
            '?' => $this->request->getQueryParams(),
        ]) ?>
        <span aria-current="page"><?= __('Cards') ?></span>
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
    ) + ['clearAction' => 'cards']) ?>
    <div class="badge-card-grid">
        <?php foreach ($badges as $badge) : ?>
            <article class="badge-catalogue-card">
                <?= $this->element('badge_product_card', ['badge' => $badge]) ?>
                <div class="badge-catalogue-card__status"><?= h($badge->status->label()) ?></div>
                <div class="badge-catalogue-card__actions">
                    <?= $this->Html->link(__('View'), ['action' => 'view', $badge->id]) ?>
                    <?= $this->Html->link(__('Transactions'), ['action' => 'stockTransactions', $badge->id]) ?>
                    <?= $this->Html->link(__('Edit'), [
                        'action' => 'edit',
                        $badge->id,
                        '?' => $badge->unlisted_badge ? ['unlisted' => 'true'] : [],
                    ]) ?>
                    <?php if ($badge->status === BadgeStatus::Unstocked) : ?>
                        <?= $this->Form->postLink(__('Stock'), [
                            'action' => 'activate',
                            $badge->id,
                            '?' => $this->request->getQueryParams(),
                        ], ['confirm' => __('Are you sure you want to mark this badge as stocked?')]) ?>
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
                </div>
            </article>
        <?php endforeach; ?>
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
        <p><?= $this->Paginator->counter(
            __('Page {{page}} of {{pages}}, showing {{current}} record(s) out of {{count}} total'),
        ) ?></p>
    </div>
</div>
