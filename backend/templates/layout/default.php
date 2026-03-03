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

    <?= $this->Html->css(['normalize.min', 'milligram.min', 'fonts', 'cake']) ?>

    <?= $this->fetch('meta') ?>
    <?= $this->fetch('css') ?>
    <?= $this->fetch('script') ?>
</head>
<body>
    <nav class="top-nav" data-nav>
        <div class="top-nav-title">
            <a href="<?= $this->Url->build('/') ?>">District Badges</a>
            <span class="top-nav-subtitle">Operations</span>
        </div>
        <button class="top-nav-toggle" type="button" aria-expanded="false" aria-controls="top-nav-links" data-nav-toggle>
            <span class="sr-only">Toggle navigation</span>
            <span class="top-nav-toggle__bar"></span>
            <span class="top-nav-toggle__bar"></span>
            <span class="top-nav-toggle__bar"></span>
        </button>
        <div class="top-nav-links" id="top-nav-links" data-nav-links>
            <?= $this->Html->link('Audits', ['controller' => 'Audits', 'action' => 'index']) ?>
            <?= $this->Html->link('Replenishments', ['controller' => 'Replenishments', 'action' => 'index']) ?>
            <?= $this->Html->link('Fulfilments', ['controller' => 'Fulfilments', 'action' => 'index']) ?>
            <?= $this->Html->link('Customer Orders', ['controller' => 'Orders', 'action' => 'index']) ?>
            <?= $this->Html->link('Groups', ['controller' => 'Groups', 'action' => 'index']) ?>
            <?= $this->Html->link('Accounts', ['controller' => 'Accounts', 'action' => 'index']) ?>
            <?= $this->Html->link('Badges', ['controller' => 'Badges', 'action' => 'index']) ?>
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
