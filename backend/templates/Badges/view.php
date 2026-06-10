<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Badge $badge
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('Edit Badge'), ['action' => 'edit', $badge->id], ['class' => 'side-nav-item']) ?>
            <?= $this->Form->postLink(__('Delete Badge'), ['action' => 'delete', $badge->id], ['confirm' => __('Are you sure you want to delete this badge?'), 'class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('List Badges'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('New Badge'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="badges view content">
            <h3><?= h($badge->badge_name) ?></h3>
            <table>
                <tr>
                    <th><?= __('Status') ?></th>
                    <td><?= h($badge->status->label()) ?></td>
                </tr>
                <tr>
                    <th><?= __('Badge Name') ?></th>
                    <td><?= h($badge->badge_name) ?></td>
                </tr>
                <tr>
                    <th><?= __('Image') ?></th>
                    <td><img src="<?= $badge->image_large_url ?>" alt="<?= h($badge->badge_name) ?>"></td>
                </tr>
                <tr>
                    <th><?= __('National Product Code') ?></th>
                    <td><?= $badge->national_product_code === null ? '' : h((string)$badge->national_product_code) ?></td>
                </tr>
                <tr>
                    <th><?= __('Stocked') ?></th>
                    <td><?= $badge->stocked ? __('Yes') : __('No') ?></td>
                </tr>
                <tr>
                    <th><?= __('Price') ?></th>
                    <td><?= $this->Number->currency($badge->price) ?></td>
                </tr>
                <tr>
                    <th><?= __('Replenishment Price') ?></th>
                    <td><?= $this->Number->currency($badge->replenishment_price) ?></td>
                </tr>
            </table>
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
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </div>
</div>
