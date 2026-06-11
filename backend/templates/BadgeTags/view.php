<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\BadgeTag $badgeTag
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(
                __('Edit Badge Tag'),
                ['action' => 'edit', $badgeTag->id],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->Form->postLink(
                __('Delete Badge Tag'),
                ['action' => 'delete', $badgeTag->id],
                [
                    'confirm' => __('Are you sure you want to delete # {0}?', $badgeTag->id),
                    'class' => 'side-nav-item',
                ],
            ) ?>
            <?= $this->Html->link(__('List Badge Tags'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('New Badge Tag'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="badgeTags view content">
            <h3><?= h($badgeTag->tag_name) ?></h3>
            <table>
                <tr>
                    <th><?= __('Id') ?></th>
                    <td><?= h($badgeTag->id) ?></td>
                </tr>
                <tr>
                    <th><?= __('Tag Name') ?></th>
                    <td><?= h($badgeTag->tag_name) ?></td>
                </tr>
                <tr>
                    <th><?= __('Tag Search Text') ?></th>
                    <td><?= h($badgeTag->tag_search_text) ?></td>
                </tr>
                <tr>
                    <th><?= __('Tag Category') ?></th>
                    <td><?= h($badgeTag->tag_category->label()) ?></td>
                </tr>
                <tr>
                    <th><?= __('Tag Order') ?></th>
                    <td><?= $this->Number->format($badgeTag->tag_order) ?></td>
                </tr>
            </table>
            <div class="related">
                <h4><?= __('Related Badges') ?></h4>
                <?php if (!empty($badgeTag->badges)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Id') ?></th>
                            <th><?= __('Badge Name') ?></th>
                            <th><?= __('National Product Code') ?></th>
                            <th><?= __('National Data') ?></th>
                            <th><?= __('Stocked') ?></th>
                            <th><?= __('On Hand Quantity') ?></th>
                            <th><?= __('Receipted Quantity') ?></th>
                            <th><?= __('Pending Quantity') ?></th>
                            <th><?= __('Latest Hash') ?></th>
                            <th><?= __('Price') ?></th>
                            <th><?= __('Fulfilled Quantity') ?></th>
                            <th><?= __('Replenishment Price') ?></th>
                            <th><?= __('Status') ?></th>
                            <th><?= __('Reserve Quantity') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($badgeTag->badges as $badge) : ?>
                        <tr>
                            <td><?= h($badge->id) ?></td>
                            <td><?= h($badge->badge_name) ?></td>
                            <td><?= h($badge->national_product_code) ?></td>
                            <td><?= h($badge->national_data) ?></td>
                            <td><?= h($badge->stocked) ?></td>
                            <td><?= h($badge->on_hand_quantity) ?></td>
                            <td><?= h($badge->receipted_quantity) ?></td>
                            <td><?= h($badge->pending_quantity) ?></td>
                            <td><?= h($badge->latest_hash) ?></td>
                            <td><?= h($badge->price) ?></td>
                            <td><?= h($badge->fulfilled_quantity) ?></td>
                            <td><?= h($badge->replenishment_price) ?></td>
                            <td><?= h($badge->status) ?></td>
                            <td><?= h($badge->reserve_quantity) ?></td>
                            <td class="actions">
                                <?= $this->Html->link(
                                    __('View'),
                                    ['controller' => 'Badges', 'action' => 'view', $badge->id],
                                ) ?>
                                <?= $this->Html->link(
                                    __('Edit'),
                                    ['controller' => 'Badges', 'action' => 'edit', $badge->id],
                                ) ?>
                                <?= $this->Form->postLink(
                                    __('Delete'),
                                    ['controller' => 'Badges', 'action' => 'delete', $badge->id],
                                    [
                                        'method' => 'delete',
                                        'confirm' => __('Are you sure you want to delete # {0}?', $badge->id),
                                    ],
                                ) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
