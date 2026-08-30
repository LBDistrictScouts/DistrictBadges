<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Badge $badge
 */
$badgeSections = $badge->badge_sections;
$badgeTypes = $badge->badge_types;
?>
<div class="row">
    <aside class="column badge-view-sidebar">
        <?= $this->element('badge_product_card', ['badge' => $badge]) ?>
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(
                __('Edit Badge'),
                [
                    'action' => 'edit',
                    $badge->id,
                    '?' => $badge->unlisted_badge ? ['unlisted' => 'true'] : [],
                ],
                ['class' => 'side-nav-item'],
            ) ?>
            <?php if ($badge->canBeDeleted()) : ?>
                <?= $this->Form->postLink(
                    __('Delete Badge'),
                    ['action' => 'delete', $badge->id],
                    [
                        'confirm' => __('Are you sure you want to delete this badge?'),
                        'class' => 'side-nav-item',
                    ],
                ) ?>
            <?php endif; ?>
            <?= $this->Html->link(__('List Badges'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(
                __('Stock Transactions'),
                ['action' => 'stockTransactions', $badge->id],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->Html->link(__('New Badge'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="badges view content">
            <h3><?= h($badge->badge_name) ?></h3>
            <div class="badge-tag-groups">
                <?php if (count($badgeSections) > 0) : ?>
                    <section class="badge-tag-group">
                        <span class="badge-tag-group__label"><?= __('Sections') ?></span>
                        <div class="badge-tag-list">
                            <?php foreach ($badgeSections as $section) : ?>
                                <span class="badge-tag badge-tag--section">
                                    <?= h($section->tag_name) ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>
                <?php if (count($badgeTypes) > 0) : ?>
                    <section class="badge-tag-group">
                        <span class="badge-tag-group__label"><?= __('Badge Types') ?></span>
                        <div class="badge-tag-list">
                            <?php foreach ($badgeTypes as $type) : ?>
                                <span class="badge-tag badge-tag--type">
                                    <?= h($type->tag_name) ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>
            </div>
            <dl class="badge-detail-grid">
                <div class="badge-detail-item">
                    <dt><?= __('Status') ?></dt>
                    <dd><?= h($badge->status->label()) ?></dd>
                </div>
                <div class="badge-detail-item">
                    <?php if ($badge->unlisted_badge) : ?>
                        <dt><?= __('Unlisted Badge') ?></dt>
                        <dd class="badge-detail-tick">
                            <span aria-hidden="true">✓</span>
                            <?= __('Yes') ?>
                        </dd>
                    <?php else : ?>
                        <dt><?= __('National Product Code') ?></dt>
                        <dd><?= h((string)$badge->national_product_code) ?></dd>
                    <?php endif; ?>
                </div>
                <div class="badge-detail-item">
                    <dt><?= __('Stocked') ?></dt>
                    <dd class="badge-detail-tick badge-detail-tick--<?= $badge->stocked ? 'yes' : 'no' ?>">
                        <span aria-hidden="true"><?= $badge->stocked ? '✓' : '—' ?></span>
                        <?= $badge->stocked ? __('Yes') : __('No') ?>
                    </dd>
                </div>
                <div class="badge-detail-item">
                    <dt><?= __('Price') ?></dt>
                    <dd><?= $this->Number->currency($badge->price) ?></dd>
                </div>
                <div class="badge-detail-item">
                    <dt><?= __('Replenishment Price') ?></dt>
                    <dd><?= $this->Number->currency($badge->replenishment_price) ?></dd>
                </div>
            </dl>
            <div class="related badge-stock-summary">
                <h4><?= __('Stock Amounts') ?></h4>
                <div class="badge-stock-groups">
                    <section class="badge-stock-group">
                        <h5><?= __('Calculated Stock') ?></h5>
                        <div class="badge-stock-cards">
                            <article class="badge-stock-card badge-stock-card--on-hand">
                                <span class="badge-stock-card__label"><?= __('On Hand') ?></span>
                                <strong class="badge-stock-card__amount" data-stock-amount="on-hand">
                                    <?= $this->Number->format($badge->on_hand_quantity) ?>
                                </strong>
                            </article>
                            <article class="badge-stock-card badge-stock-card--pending">
                                <span class="badge-stock-card__label"><?= __('Pending') ?></span>
                                <strong class="badge-stock-card__amount" data-stock-amount="pending">
                                    <?= $this->Number->format($badge->pending_quantity) ?>
                                </strong>
                            </article>
                            <article class="badge-stock-card badge-stock-card--reserve">
                                <span class="badge-stock-card__label"><?= __('Reserve') ?></span>
                                <strong class="badge-stock-card__amount" data-stock-amount="reserve">
                                    <?= $this->Number->format($badge->reserve_quantity) ?>
                                </strong>
                            </article>
                        </div>
                    </section>
                    <section class="badge-stock-group">
                        <h5><?= __('Historic Stock Movements') ?></h5>
                        <div class="badge-stock-cards">
                            <article class="badge-stock-card badge-stock-card--receipted">
                                <span class="badge-stock-card__label"><?= __('Receipted') ?></span>
                                <strong class="badge-stock-card__amount" data-stock-amount="receipted">
                                    <?= $this->Number->format($badge->receipted_quantity) ?>
                                </strong>
                            </article>
                            <article class="badge-stock-card badge-stock-card--fulfilled">
                                <span class="badge-stock-card__label"><?= __('Fulfilled') ?></span>
                                <strong class="badge-stock-card__amount" data-stock-amount="fulfilled">
                                    <?= $this->Number->format($badge->fulfilled_quantity) ?>
                                </strong>
                            </article>
                            <article class="badge-stock-card badge-stock-card--invoiced">
                                <span class="badge-stock-card__label"><?= __('Invoiced') ?></span>
                                <strong class="badge-stock-card__amount" data-stock-amount="invoiced">
                                    <?= $this->Number->format($badge->invoiced_quantity) ?>
                                </strong>
                            </article>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </div>
</div>
