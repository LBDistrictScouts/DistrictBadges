<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         0.10.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 * @var \App\View\AppView $this
 */

$appDescription = 'District Badges Operations';
$controller = (string)$this->getRequest()->getParam('controller');
$entityCategories = [
    'Audits' => 'audits',
    'AuditLines' => 'audits',
    'Replenishments' => 'replenishments',
    'ReplenishmentOrderLines' => 'replenishments',
    'ReplenishmentReceiptLines' => 'replenishments',
    'Fulfilments' => 'fulfilments',
    'FulfilmentLines' => 'fulfilments',
    'Orders' => 'orders',
    'OrderLines' => 'orders',
    'Badges' => 'badges',
    'BadgeSections' => 'badges',
    'BadgeTags' => 'badges',
    'BadgeTypes' => 'badges',
    'StockTransactions' => 'badges',
];
$entityCategory = $entityCategories[$controller] ?? 'default';
?>
<!DOCTYPE html>
<html lang="EN-GB">
<head>
    <?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>
        <?= $appDescription ?>:
        <?= $this->fetch('title') ?>
    </title>
    <?= $this->Html->meta('icon') ?>

    <?= $this->Html->css([
        'normalize.min',
        'milligram.min',
        'fonts',
        'vendor/select2.min',
        'cake',
    ]) ?>
    <?= $this->Html->script(['vendor/jquery.min', 'vendor/select2.min']) ?>

    <?= $this->fetch('meta') ?>
    <?= $this->fetch('css') ?>
    <?= $this->fetch('script') ?>
</head>
<body data-entity-category="<?= h($entityCategory) ?>">
    <nav class="top-nav" data-nav>
        <div class="top-nav-title">
            <a href="<?= $this->Url->build('/') ?>" class="district-brand">
                <span class="district-brand__mark" aria-hidden="true">LBA</span>
                <span class="district-brand__copy"><strong>LBA Scouts</strong><small>District Badge Shop · Operations</small></span>
            </a>
        </div>
        <button
            class="top-nav-toggle"
            type="button"
            aria-expanded="false"
            aria-controls="top-nav-links"
            data-nav-toggle
        >
            <span class="sr-only">Toggle navigation</span>
            <span class="top-nav-toggle__bar"></span>
            <span class="top-nav-toggle__bar"></span>
            <span class="top-nav-toggle__bar"></span>
        </button>
        <div class="top-nav-links" id="top-nav-links" data-nav-links>
            <?= $this->Html->link('Orders', ['controller' => 'Orders', 'action' => 'index'], ['class' => $entityCategory === 'orders' ? 'is-active' : '']) ?>
            <?= $this->Html->link('Fulfilments', ['controller' => 'Fulfilments', 'action' => 'index'], ['class' => $entityCategory === 'fulfilments' ? 'is-active' : '']) ?>
            <?= $this->Html->link('Replenishments', ['controller' => 'Replenishments', 'action' => 'index'], ['class' => $entityCategory === 'replenishments' ? 'is-active' : '']) ?>
            <?= $this->Html->link('Audits', ['controller' => 'Audits', 'action' => 'index'], ['class' => $entityCategory === 'audits' ? 'is-active' : '']) ?>
            <?= $this->Html->link('Badges', ['controller' => 'Badges', 'action' => 'index'], ['class' => $entityCategory === 'badges' ? 'is-active' : '']) ?>
        </div>
        <div class="top-nav-overlay" data-nav-overlay></div>
    </nav>
    <main class="main">
        <div class="container">
            <?= $this->Flash->render() ?>
            <?= $this->fetch('content') ?>
        </div>
    </main>
    <footer>
    </footer>
    <script>
        (function () {
            var toggle = document.querySelector('[data-nav-toggle]');
            var links = document.querySelector('[data-nav-links]');
            if (!toggle || !links) {
                return;
            }

            var overlay = document.querySelector('[data-nav-overlay]');
            var closeMenu = function () {
                toggle.setAttribute('aria-expanded', 'false');
                links.classList.remove('top-nav-links--open');
                if (overlay) {
                    overlay.classList.remove('top-nav-overlay--open');
                }
            };

            var openMenu = function () {
                toggle.setAttribute('aria-expanded', 'true');
                links.classList.add('top-nav-links--open');
                if (overlay) {
                    overlay.classList.add('top-nav-overlay--open');
                }
            };

            toggle.addEventListener('click', function () {
                var expanded = toggle.getAttribute('aria-expanded') === 'true';
                if (expanded) {
                    closeMenu();
                } else {
                    openMenu();
                }
            });

            if (overlay) {
                overlay.addEventListener('click', closeMenu);
            }
        })();
    </script>
</body>
</html>
