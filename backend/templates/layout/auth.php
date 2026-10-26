<?php
/** @var \App\View\AppView $this */
?>
<!DOCTYPE html>
<html lang="en-GB">
<head>
    <?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>District Badge Shop · Sign in</title>
    <?= $this->Html->meta('icon', '/lba-icon.svg', ['type' => 'image/svg+xml']) ?>
    <?= $this->Html->css(['normalize.min', 'milligram.min', 'fonts', 'cake']) ?>
    <?= $this->fetch('meta') ?>
    <?= $this->fetch('css') ?>
</head>
<body>
    <main class="main">
        <div class="container">
            <?= $this->fetch('content') ?>
        </div>
    </main>
</body>
</html>
