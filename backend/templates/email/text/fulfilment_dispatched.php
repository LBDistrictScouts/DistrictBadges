<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Fulfilment $fulfilment
 * @var \App\Model\Entity\User $user
 * @var string|null $contactName
 */
use App\Model\Enum\DispatchType;

$customerName = trim((string)($contactName ?? $user->full_name)) ?: 'there';
$dispatchedDate = $fulfilment->dispatched_date?->i18nFormat('d MMMM yyyy, HH:mm') ?? '';
$dispatchType = $fulfilment->dispatch_type;
$address = array_values(array_filter([
    $fulfilment->dispatch_address_line_1,
    $fulfilment->dispatch_address_line_2,
    $fulfilment->dispatch_town,
    $fulfilment->dispatch_county,
    $fulfilment->dispatch_postcode,
], static fn($line): bool => trim((string)$line) !== ''));
$postageCharge = '£' . number_format((float)$fulfilment->postage_charge, 2);
$emailCopy = match ($dispatchType) {
    DispatchType::PostalDispatch => [
        'eyebrow' => 'BADGES DISPATCHED BY POST',
        'heading' => 'Your badges are on their way.',
        'intro' => 'The district badge shop team has posted the badges below to your dispatch address.',
        'summary' => 'DISPATCH SUMMARY',
        'next' => 'Your badges have been sent to the address shown above. The postage charge for this dispatch will be included on your Group’s invoice.',
    ],
    DispatchType::LocalDropOff => [
        'eyebrow' => 'BADGES READY FOR LOCAL DROP OFF',
        'heading' => 'Your badges are ready for drop-off.',
        'intro' => 'The district badge shop team has prepared the badges below for local drop-off.',
        'summary' => 'DROP-OFF SUMMARY',
        'next' => 'The district team will deliver your badges to the address shown above. Please contact the badge shop if the delivery details need to change.',
    ],
    DispatchType::ShopCollection => [
        'eyebrow' => 'BADGES READY TO COLLECT',
        'heading' => 'Your badges are ready.',
        'intro' => 'The district badge shop team has prepared the badges below. They are now ready for collection.',
        'summary' => 'COLLECTION SUMMARY',
        'next' => 'Please arrange collection with the district badge shop team.',
    ],
};
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
<?= $emailCopy['eyebrow'] ?>

Hi, <?= $customerName ?>. <?= $emailCopy['heading'] ?>

<?= $emailCopy['intro'] ?>

Fulfilment: <?= $fulfilment->fulfilment_number ?>
Prepared: <?= $dispatchedDate ?>
<?php if ($orderNumbers !== []) : ?>
<?= count($orderNumbers) === 1 ? 'Order' : 'Orders' ?>: <?= implode(', ', array_keys($orderNumbers)) ?>
<?php endif; ?>

Dispatch type: <?= $dispatchType->label() ?>
<?php if ($dispatchType === DispatchType::PostalDispatch) : ?>
Postage charge: <?= $postageCharge ?>
<?php endif; ?>
<?php if ($dispatchType !== DispatchType::ShopCollection && $address !== []) : ?>
Dispatch address:
<?= implode("\n", $address) ?>
<?php endif; ?>

<?= $emailCopy['summary'] ?>
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
<?= $emailCopy['next'] ?>

Letchworth, Baldock & Ashwell Scouts
Preparing young people with #SkillsForLife
Registered Charity 279860
