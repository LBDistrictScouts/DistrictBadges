<?php
use App\Model\Enum\TagCategory;

/**
 * @var \App\View\AppView $this
 * @var array{orders: int, replenishments: int, fulfilments: int} $dashboardCounts
 */
$this->assign('title', 'Operations');
echo $this->Html->css('home', ['block' => true]);
?>

<section class="operations-home">
    <header class="operations-welcome">
        <div class="operations-welcome__copy">
            <p class="operations-eyebrow">District badge shop</p>
            <h1>What would you like<br><span>to do today?</span></h1>
            <p>Manage badge stock and orders for the district. Choose a common task below, or browse all records.</p>
        </div>
        <div class="operations-welcome__mark" aria-hidden="true"><span>LBA</span><small>Scouts</small></div>
    </header>

    <div class="operations-primary" aria-label="Common tasks">
        <article class="task-card task-card--purple">
            <div class="task-card__metric"><strong><?= $this->Number->format($dashboardCounts['orders']) ?></strong><span>orders awaiting fulfilment</span></div>
            <div><p class="operations-eyebrow">Customer orders</p><h2>Create an order</h2><p>Place a badge order for a group and track it through fulfilment.</p></div>
            <div class="task-card__actions">
                <?= $this->Html->link('Create an order', ['controller' => 'Orders', 'action' => 'add'], ['class' => 'district-button']) ?>
                <?= $this->Html->link('View orders', ['controller' => 'Orders', 'action' => 'index'], ['class' => 'district-text-link']) ?>
            </div>
        </article>
        <article class="task-card task-card--teal">
            <div class="task-card__metric"><strong><?= $this->Number->format($dashboardCounts['replenishments']) ?></strong><span>replenishments awaiting stock</span></div>
            <div><p class="operations-eyebrow">Order stock</p><h2>Replenish badges</h2><p>Build a stock request and receive it when it arrives.</p></div>
            <div class="task-card__actions">
                <?= $this->Html->link('New replenishment', ['controller' => 'Replenishments', 'action' => 'add'], ['class' => 'district-button']) ?>
                <?= $this->Html->link('View replenishments', ['controller' => 'Replenishments', 'action' => 'index'], ['class' => 'district-text-link']) ?>
            </div>
        </article>
        <article class="task-card task-card--green">
            <div class="task-card__metric"><strong><?= $this->Number->format($dashboardCounts['fulfilments']) ?></strong><span>fulfilments sent in 7 days</span></div>
            <div><p class="operations-eyebrow">Prepare orders</p><h2>Create a fulfilment</h2><p>Gather badges for customer orders and mark them ready.</p></div>
            <div class="task-card__actions">
                <?= $this->Html->link('New fulfilment', ['controller' => 'Fulfilments', 'action' => 'add'], ['class' => 'district-button']) ?>
                <?= $this->Html->link('View fulfilments', ['controller' => 'Fulfilments', 'action' => 'index'], ['class' => 'district-text-link']) ?>
            </div>
        </article>
    </div>

    <section class="operations-browse">
        <div class="operations-browse__heading"><p class="operations-eyebrow">Browse records</p><h2>Everything else</h2></div>
        <nav class="record-links" aria-label="Browse records">
            <?= $this->Html->link('<span>Audits</span><small>Count stock and review differences</small>', ['controller' => 'Audits', 'action' => 'index'], ['escape' => false]) ?>
            <?= $this->Html->link('<span>Badge catalogue</span><small>Products, prices and stock</small>', ['controller' => 'Badges', 'action' => 'index'], ['escape' => false]) ?>
            <?= $this->Html->link('<span>Badge Type Tags</span><small>Manage catalogue classifications</small>', ['controller' => 'BadgeTags', 'action' => 'index', '?' => ['category' => TagCategory::BadgeTypes->value]], ['escape' => false]) ?>
            <?= $this->Html->link('<span>Section Tags</span><small>Manage badge section tags</small>', ['controller' => 'BadgeTags', 'action' => 'index', '?' => ['category' => TagCategory::Sections->value]], ['escape' => false]) ?>
            <?= $this->Html->link('<span>Groups</span><small>Manage customer groups</small>', ['controller' => 'Groups', 'action' => 'index'], ['escape' => false]) ?>
            <?= $this->Html->link('<span>Accounts</span><small>Billing and account details</small>', ['controller' => 'Accounts', 'action' => 'index'], ['escape' => false]) ?>
            <?= $this->Html->link('<span>Invoices</span><small>Create invoices and manage billing dates</small>', ['controller' => 'Invoices', 'action' => 'index'], ['escape' => false]) ?>
            <?= $this->Html->link('<span>Users</span><small>Manage operations access</small>', ['controller' => 'Users', 'action' => 'index'], ['escape' => false]) ?>
        </nav>
    </section>
</section>
