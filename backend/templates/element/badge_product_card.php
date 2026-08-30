<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Badge $badge
 */
$badgeSections = $badge->badge_sections ?? [];
$badgeTypes = $badge->badge_types ?? [];
?>
<article class="badge-product-card">
    <div class="badge-product-card__image">
        <?php if ($badge->image_medium_url !== null) : ?>
            <img
                src="<?= h($badge->image_medium_url) ?>"
                alt="<?= h($badge->badge_name) ?>"
                loading="lazy"
            >
        <?php else : ?>
            <span class="badge-product-card__placeholder"><?= __('Badge') ?></span>
        <?php endif; ?>
    </div>
    <div class="badge-product-card__content">
        <h3><?= h($badge->badge_name) ?></h3>
        <strong class="badge-product-card__price">
            <?= $this->Number->currency($badge->price) ?>
        </strong>
        <div class="badge-product-card__tags">
            <?php foreach ($badgeSections as $section) : ?>
                <span data-tag="<?= h(mb_strtolower($section->tag_name)) ?>">
                    <?= h($section->tag_name) ?>
                </span>
            <?php endforeach; ?>
            <?php foreach ($badgeTypes as $type) : ?>
                <span data-tag="<?= h(mb_strtolower($type->tag_name)) ?>">
                    <?= h($type->tag_name) ?>
                </span>
            <?php endforeach; ?>
        </div>
    </div>
</article>
