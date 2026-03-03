<?php
/**
 * @var \App\View\AppView $this
 */
$this->assign('title', 'Operations Home');
echo $this->Html->css('home', ['block' => true]);
?>

<section class="ops-home">
    <div class="ops-hero">
        <div class="ops-hero__copy">
            <p class="ops-kicker">Operations Console</p>
            <h1>Start audits, build replenishment orders, and fulfil stock without hunting through menus.</h1>
            <p class="ops-subtitle">Launch the three core workflows with one click, then jump straight to the record lists when you need details.</p>
            <div class="ops-actions">
                <?= $this->Html->link('Start Audit', ['controller' => 'Audits', 'action' => 'add'], ['class' => 'ops-btn ops-btn--primary']) ?>
                <?= $this->Html->link('Create Replenishment', ['controller' => 'Replenishments', 'action' => 'add'], ['class' => 'ops-btn ops-btn--accent']) ?>
                <?= $this->Html->link('Create Fulfilment', ['controller' => 'Fulfilments', 'action' => 'add'], ['class' => 'ops-btn ops-btn--ghost']) ?>
            </div>
        </div>
        <div class="ops-hero__panel">
            <div class="ops-panel__header">
                <span class="ops-panel__title">Quick Access</span>
                <span class="ops-panel__hint">Jump to lists</span>
            </div>
            <div class="ops-panel__links">
                <?= $this->Html->link('Audits', ['controller' => 'Audits', 'action' => 'index']) ?>
                <?= $this->Html->link('Audit Lines', ['controller' => 'AuditLines', 'action' => 'index']) ?>
                <?= $this->Html->link('Replenishments', ['controller' => 'Replenishments', 'action' => 'index']) ?>
                <?= $this->Html->link('Replenishment Order Lines', ['controller' => 'ReplenishmentOrderLines', 'action' => 'index']) ?>
                <?= $this->Html->link('Replenishment Receipt Lines', ['controller' => 'ReplenishmentReceiptLines', 'action' => 'index']) ?>
                <?= $this->Html->link('Fulfilments', ['controller' => 'Fulfilments', 'action' => 'index']) ?>
                <?= $this->Html->link('Fulfilment Lines', ['controller' => 'FulfilmentLines', 'action' => 'index']) ?>
                <?= $this->Html->link('Customer Orders', ['controller' => 'Orders', 'action' => 'index']) ?>
                <?= $this->Html->link('Order Lines', ['controller' => 'OrderLines', 'action' => 'index']) ?>
                <?= $this->Html->link('Groups', ['controller' => 'Groups', 'action' => 'index']) ?>
                <?= $this->Html->link('Accounts', ['controller' => 'Accounts', 'action' => 'index']) ?>
            </div>
        </div>
    </div>

    <div class="ops-grid">
        <article class="ops-card">
            <div class="ops-card__top">
                <p class="ops-card__eyebrow">Audit Cycle</p>
                <h2>Capture the current stock reality.</h2>
            </div>
            <p class="ops-card__body">Kick off a full audit, then add audit lines as items are counted and confirmed.</p>
            <div class="ops-card__actions">
                <?= $this->Html->link('New Audit', ['controller' => 'Audits', 'action' => 'add'], ['class' => 'ops-btn ops-btn--primary']) ?>
                <?= $this->Html->link('View Audits', ['controller' => 'Audits', 'action' => 'index'], ['class' => 'ops-link']) ?>
            </div>
        </article>

        <article class="ops-card">
            <div class="ops-card__top">
                <p class="ops-card__eyebrow">Replenishment</p>
                <h2>Raise new stock requests fast.</h2>
            </div>
            <p class="ops-card__body">Create replenishment orders, then track incoming receipts as they land.</p>
            <div class="ops-card__actions">
                <?= $this->Html->link('New Replenishment', ['controller' => 'Replenishments', 'action' => 'add'], ['class' => 'ops-btn ops-btn--accent']) ?>
                <?= $this->Html->link('View Replenishments', ['controller' => 'Replenishments', 'action' => 'index'], ['class' => 'ops-link']) ?>
            </div>
        </article>

        <article class="ops-card">
            <div class="ops-card__top">
                <p class="ops-card__eyebrow">Fulfilment</p>
                <h2>Move orders out the door.</h2>
            </div>
            <p class="ops-card__body">Create fulfilments and fulfilment lines to keep outbound stock current.</p>
            <div class="ops-card__actions">
                <?= $this->Html->link('New Fulfilment', ['controller' => 'Fulfilments', 'action' => 'add'], ['class' => 'ops-btn ops-btn--ghost']) ?>
                <?= $this->Html->link('View Fulfilments', ['controller' => 'Fulfilments', 'action' => 'index'], ['class' => 'ops-link']) ?>
            </div>
        </article>

        <article class="ops-card">
            <div class="ops-card__top">
                <p class="ops-card__eyebrow">Customer Orders</p>
                <h2>Track orders and their line items.</h2>
            </div>
            <p class="ops-card__body">Review customer orders, then drill into order lines for item-level updates.</p>
            <div class="ops-card__actions">
                <?= $this->Html->link('New Order', ['controller' => 'Orders', 'action' => 'add'], ['class' => 'ops-btn ops-btn--accent']) ?>
                <?= $this->Html->link('View Orders', ['controller' => 'Orders', 'action' => 'index'], ['class' => 'ops-link']) ?>
            </div>
        </article>

        <article class="ops-card">
            <div class="ops-card__top">
                <p class="ops-card__eyebrow">Groups</p>
                <h2>Manage customer organizations.</h2>
            </div>
            <p class="ops-card__body">Create or update group records that tie accounts and orders together.</p>
            <div class="ops-card__actions">
                <?= $this->Html->link('New Group', ['controller' => 'Groups', 'action' => 'add'], ['class' => 'ops-btn ops-btn--ghost']) ?>
                <?= $this->Html->link('View Groups', ['controller' => 'Groups', 'action' => 'index'], ['class' => 'ops-link']) ?>
            </div>
        </article>

        <article class="ops-card">
            <div class="ops-card__top">
                <p class="ops-card__eyebrow">Accounts</p>
                <h2>Maintain account details.</h2>
            </div>
            <p class="ops-card__body">Keep billing and operational details aligned for each account.</p>
            <div class="ops-card__actions">
                <?= $this->Html->link('New Account', ['controller' => 'Accounts', 'action' => 'add'], ['class' => 'ops-btn ops-btn--ghost']) ?>
                <?= $this->Html->link('View Accounts', ['controller' => 'Accounts', 'action' => 'index'], ['class' => 'ops-link']) ?>
            </div>
        </article>
    </div>

    <div class="ops-rail">
        <div class="ops-rail__card">
            <div>
                <h3>Need badge context?</h3>
                <p>Jump into badge inventory and stock transactions before taking action.</p>
            </div>
            <div class="ops-rail__actions">
                <?= $this->Html->link('Badges', ['controller' => 'Badges', 'action' => 'index'], ['class' => 'ops-link']) ?>
                <?= $this->Html->link('Stock Transactions', ['controller' => 'StockTransactions', 'action' => 'index'], ['class' => 'ops-link']) ?>
                <?= $this->Html->link('Customer Orders', ['controller' => 'Orders', 'action' => 'index'], ['class' => 'ops-link']) ?>
                <?= $this->Html->link('Groups', ['controller' => 'Groups', 'action' => 'index'], ['class' => 'ops-link']) ?>
                <?= $this->Html->link('Accounts', ['controller' => 'Accounts', 'action' => 'index'], ['class' => 'ops-link']) ?>
            </div>
        </div>
    </div>
</section>
