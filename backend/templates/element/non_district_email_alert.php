<?php
/**
 * @var \App\View\AppView $this
 * @var string $email
 * @var bool $isNonDistrictEmail
 */
?>
<?php if ($isNonDistrictEmail) : ?>
<div class="message error non-district-email-alert" role="alert">
    <span class="non-district-email-alert__icon" aria-hidden="true">⚠</span>
    <div>
        <h4><?= __('Danger: Non-District Email') ?></h4>
        <p>
            <?= __('The order was created with {0}, which is outside the registered domains for this group.', h($email)) ?>
        </p>
        <p><?= __('Verify the customer before fulfilling the order.') ?></p>
    </div>
</div>
<?php endif; ?>
