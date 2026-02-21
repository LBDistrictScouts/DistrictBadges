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
                    <th><?= __('Badge Name') ?></th>
                    <td><?= h($badge->badge_name) ?></td>
                </tr>
                <tr>
                    <th><?= __('National Data') ?></th>
                    <td><?= h($badge->national_data) ?></td>
                </tr>
                <tr>
                    <th><?= __('National Product Code') ?></th>
                    <td><?= $badge->national_product_code === null ? '' : h((string)$badge->national_product_code) ?></td>
                </tr>
                <tr>
                    <th><?= __('Stocked') ?></th>
                    <td><?= $badge->stocked ? __('Yes') : __('No') ?></td>
                </tr>
            </table>
        </div>
    </div>
</div>
