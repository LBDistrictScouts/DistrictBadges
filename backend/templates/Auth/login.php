<?php
/** @var \App\View\AppView $this */
/** @var bool $configured */
/** @var string|null $errorMessage */
?>
<section class="row" style="justify-content: center; margin-top: 8rem;">
    <div class="column column-50">
        <h1>District Badge Shop</h1>
        <h2>Staff sign in</h2>
        <p>Sign in with your approved staff account to manage the badge system.</p>
        <?php if ($errorMessage): ?>
            <p class="error-message" role="alert"><?= h($errorMessage) ?></p>
        <?php endif; ?>
        <?php if ($configured): ?>
            <?= $this->Html->link('Continue with staff sign-in', ['action' => 'start'], ['class' => 'button']) ?>
        <?php else: ?>
            <p class="error-message" role="alert">Cognito sign-in has not been configured for this environment.</p>
        <?php endif; ?>
    </div>
</section>
