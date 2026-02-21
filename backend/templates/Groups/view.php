<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Group $group
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('Edit Group'), ['action' => 'edit', $group->id], ['class' => 'side-nav-item']) ?>
            <?= $this->Form->postLink(__('Delete Group'), ['action' => 'delete', $group->id], ['confirm' => __('Are you sure you want to delete this group?'), 'class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('List Groups'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('New Group'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="groups view content">
            <h3><?= h($group->group_name) ?></h3>
            <table>
                <tr>
                    <th><?= __('Group Name') ?></th>
                    <td><?= h($group->group_name) ?></td>
                </tr>
                <tr>
                    <th><?= __('Group Osm Id') ?></th>
                    <td><?= h((string)$group->group_osm_id) ?></td>
                </tr>
            </table>
            <div class="related">
                <h4><?= __('Related Accounts') ?></h4>
                <?php if (!empty($group->accounts)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Account Name') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($group->accounts as $account) : ?>
                        <tr>
                            <td><?= h($account->account_name) ?></td>
                            <td class="actions">
                                <?= $this->Html->link(__('View'), ['controller' => 'Accounts', 'action' => 'view', $account->id]) ?>
                                <?= $this->Html->link(__('Edit'), ['controller' => 'Accounts', 'action' => 'edit', $account->id]) ?>
                                <?= $this->Form->postLink(
                                    __('Delete'),
                                    ['controller' => 'Accounts', 'action' => 'delete', $account->id],
                                    [
                                        'method' => 'delete',
                                        'confirm' => __('Are you sure you want to delete this account?'),
                                    ]
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
