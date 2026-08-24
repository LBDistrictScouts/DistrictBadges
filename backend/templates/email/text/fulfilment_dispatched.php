<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Fulfilment $fulfilment
 * @var \App\Model\Entity\User $user
 * @var string|null $contactName
 */

$customerName = trim((string)($contactName ?? $user->full_name)) ?: 'there';
$dispatchedDate = $fulfilment->dispatched_date?->i18nFormat('d MMMM yyyy, HH:mm') ?? '';
$orderNumbers = [];
$linesByOrder = [];
foreach ($fulfilment->fulfilment_lines as $line) {
    $orderNumber = (string)($line->order_line?->order?->order_number ?? '');
    if ($orderNumber !== '') {
        $orderNumbers[$orderNumber] = true;
    }
    $linesByOrder[$orderNumber][] = $line;
}
?>
BADGES READY TO COLLECT

Hi, <?= $customerName ?>. Your badges are ready.

The district badge shop team has prepared the badges below. They are now ready for collection.

Fulfilment: <?= $fulfilment->fulfilment_number ?>
Prepared: <?= $dispatchedDate ?>
<?php if ($orderNumbers !== []) : ?>
<?= count($orderNumbers) === 1 ? 'Order' : 'Orders' ?>: <?= implode(', ', array_keys($orderNumbers)) ?>
<?php endif; ?>

COLLECTION SUMMARY
<?php foreach ($linesByOrder as $orderNumber => $lines) : ?>
<?php if (count($orderNumbers) > 1) : ?>
ORDER <?= $orderNumber ?>
<?php endif; ?>
<?php foreach ($lines as $line) : ?>
- <?= $line->badge->badge_name ?? 'Badge' ?> — Quantity <?= (int)$line->quantity ?>
<?php endforeach; ?>
<?php endforeach; ?>

Total: <?= (int)$fulfilment->total_quantity ?> <?= (int)$fulfilment->total_quantity === 1 ? 'badge' : 'badges' ?>

WHAT HAPPENS NEXT?
Please arrange collection with the district badge shop team.

Letchworth, Baldock & Ashwell Scouts
Preparing young people with #SkillsForLife
Registered Charity 279860
