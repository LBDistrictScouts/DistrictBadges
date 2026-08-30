<?php
use App\Model\Enum\BadgeStatus;

/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Badge> $badges
 */
?>
<div class="badges index content badge-stock-index">
    <?= $this->Html->link(__('New Badge'), ['action' => 'add'], ['class' => 'button float-right']) ?>
    <h3><?= __('Badges') ?></h3>
    <div class="badge-index-view-switch">
        <?= $this->Html->link(__('Table'), [
            'action' => 'table',
            '?' => $this->request->getQueryParams(),
        ]) ?>
        <?= $this->Html->link(__('Cards'), [
            'action' => 'cards',
            '?' => $this->request->getQueryParams(),
        ]) ?>
        <span aria-current="page"><?= __('Stock') ?></span>
    </div>
    <?= $this->element('badge_filters', compact(
        'filters',
        'listedOptions',
        'sectionTagOptions',
        'statusOptions',
        'stockedOptions',
        'typeTagOptions',
    ) + ['clearAction' => 'stock', 'showStockSort' => true]) ?>
    <div class="badge-stock-grid">
        <?php foreach ($badges as $badge) : ?>
            <article class="badge-stock-tile">
                <header class="badge-stock-tile__header">
                    <h4><?= h($badge->badge_name) ?></h4>
                    <span><?= h($badge->status->label()) ?></span>
                </header>
                <div class="badge-stock-tile__tags">
                    <div class="badge-stock-tile__tag-column badge-stock-tile__tag-column--sections">
                        <?php foreach ($badge->badge_sections as $section) : ?>
                            <span
                                class="badge-tag badge-tag--section"
                                title="<?= h($section->tag_name) ?>"
                                data-stock-tag
                            ><?= h($section->tag_name) ?></span>
                        <?php endforeach; ?>
                        <span class="badge-stock-tile__tag-overflow" data-stock-tag-overflow hidden>…</span>
                    </div>
                    <div class="badge-stock-tile__tag-column badge-stock-tile__tag-column--types">
                        <?php foreach ($badge->badge_types as $type) : ?>
                            <span
                                class="badge-tag badge-tag--type"
                                title="<?= h($type->tag_name) ?>"
                                data-stock-tag
                            ><?= h($type->tag_name) ?></span>
                        <?php endforeach; ?>
                        <span class="badge-stock-tile__tag-overflow" data-stock-tag-overflow hidden>…</span>
                    </div>
                </div>
                <dl class="badge-stock-tile__counts">
                    <div>
                        <dt><?= __('On Hand') ?></dt>
                        <dd><?= $this->Number->format($badge->on_hand_quantity) ?></dd>
                    </div>
                    <div>
                        <dt><?= __('Pending') ?></dt>
                        <dd><?= $this->Number->format($badge->pending_quantity) ?></dd>
                    </div>
                    <div>
                        <dt><?= __('Reserve') ?></dt>
                        <dd><?= $this->Number->format($badge->reserve_quantity) ?></dd>
                    </div>
                    <div>
                        <dt><?= __('Receipted') ?></dt>
                        <dd><?= $this->Number->format($badge->receipted_quantity) ?></dd>
                    </div>
                    <div>
                        <dt><?= __('Fulfilled') ?></dt>
                        <dd><?= $this->Number->format($badge->fulfilled_quantity) ?></dd>
                    </div>
                    <div>
                        <dt><?= __('Invoiced') ?></dt>
                        <dd><?= $this->Number->format($badge->invoiced_quantity) ?></dd>
                    </div>
                </dl>
                <div class="badge-stock-tile__actions">
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
<?= $this->Html->script('badge-stock-tags', ['block' => true, 'defer' => true]) ?>
