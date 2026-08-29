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
        'title' => 'Your badges have been dispatched',
        'preheader' => 'Your district badge fulfilment is on its way by post.',
        'eyebrow' => 'Postal dispatch',
        'heading' => 'Your badges are on their way.',
        'intro' => 'The district badge shop team has posted the badges below to your dispatch address.',
        'summary' => 'Dispatch summary',
        'next' => 'Your badges have been sent to the address shown above. The postage charge for this dispatch will be included on your Group’s invoice.',
    ],
    DispatchType::LocalDropOff => [
        'title' => 'Your badges are ready for local drop-off',
        'preheader' => 'Your district badge fulfilment has been prepared for local delivery.',
        'eyebrow' => 'Local drop off',
        'heading' => 'Your badges are ready for drop-off.',
        'intro' => 'The district badge shop team has prepared the badges below for local drop-off.',
        'summary' => 'Drop-off summary',
        'next' => 'The district team will deliver your badges to the address shown above. Please contact the badge shop if the delivery details need to change.',
    ],
    DispatchType::ShopCollection => [
        'title' => 'Your badges are ready to collect',
        'preheader' => 'Your district badge fulfilment has been prepared and is ready for collection.',
        'eyebrow' => 'Shop collection',
        'heading' => 'Your badges are ready.',
        'intro' => 'The district badge shop team has prepared the badges below. They are now ready for collection.',
        'summary' => 'Collection summary',
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
$this->assign('title', $emailCopy['title']);
$this->assign('preheader', $emailCopy['preheader']);
?>
<div style="margin:0 0 13px; color:#25b755; font-size:12px; font-weight:900; letter-spacing:1.8px; line-height:1.3; text-transform:uppercase;"><?= h($emailCopy['eyebrow']) ?></div>
<h1 class="email-heading" style="margin:0; color:#172329; font-size:40px; font-weight:900; letter-spacing:-1.5px; line-height:1.08;">Hi, <?= h($customerName) ?>.<br><span style="color:#7413dc;"><?= h($emailCopy['heading']) ?></span></h1>
<p style="margin:22px 0 28px; color:#66747b; font-size:16px; line-height:1.65;"><?= h($emailCopy['intro']) ?></p>
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%; margin:0 0 30px; border-collapse:collapse; border-radius:12px; background:#f5f8f8;">
    <tr>
        <td width="50%" valign="top" style="width:50%; padding:20px 12px 20px 20px;"><div style="margin-bottom:5px; color:#66747b; font-size:10px; font-weight:900; letter-spacing:1px; text-transform:uppercase;">Fulfilment</div><div style="color:#172329; font-size:15px; font-weight:900;"><?= h((string)$fulfilment->fulfilment_number) ?></div></td>
        <td width="50%" valign="top" style="width:50%; padding:20px 20px 20px 12px;"><div style="margin-bottom:5px; color:#66747b; font-size:10px; font-weight:900; letter-spacing:1px; text-transform:uppercase;">Prepared</div><div style="color:#172329; font-size:15px; font-weight:900;"><?= h($dispatchedDate) ?></div></td>
    </tr>
<?php if ($orderNumbers !== []) : ?>
    <tr><td colspan="2" style="padding:0 20px 20px;"><div style="padding-top:16px; border-top:1px solid #dfe7e7; color:#66747b; font-size:13px; line-height:1.5;">For <?= count($orderNumbers) === 1 ? 'order' : 'orders' ?> <strong style="color:#172329;"><?= h(implode(', ', array_keys($orderNumbers))) ?></strong></div></td></tr>
<?php endif; ?>
</table>
<div style="margin:0 0 30px; padding:20px; border-left:4px solid #7413dc; border-radius:0 10px 10px 0; background:#f3eafd; color:#344249; font-size:14px; line-height:1.65;"><strong style="color:#172329;">Dispatch type: <?= h($dispatchType->label()) ?></strong>
<?php if ($dispatchType === DispatchType::PostalDispatch) : ?>
<br>Postage charge: <strong><?= h($postageCharge) ?></strong>
<?php endif; ?>
<?php if ($dispatchType !== DispatchType::ShopCollection && $address !== []) : ?>
<br><br><strong style="color:#172329;">Dispatch address</strong><br><?= implode('<br>', array_map('h', $address)) ?>
<?php endif; ?>
</div>
<h2 style="margin:0 0 14px; color:#172329; font-size:20px; font-weight:900; line-height:1.3;"><?= h($emailCopy['summary']) ?></h2>
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%; border-collapse:collapse;">
<?php foreach ($linesByOrder as $orderNumber => $lines) : ?>
<?php if (count($orderNumbers) > 1) : ?>
    <tr><td colspan="2" style="padding:18px 0 7px; border-bottom:1px solid #e8eded; color:#8a969c; font-size:12px; font-weight:600; letter-spacing:.2px;">Order <?= h($orderNumber) ?></td></tr>
<?php endif; ?>
<?php foreach ($lines as $line) : ?>
    <tr>
        <td valign="top" style="padding:14px 12px 14px 0; border-bottom:1px solid #e8eded; color:#344249; font-size:14px; font-weight:400; line-height:1.45;"><?= h((string)($line->badge->badge_name ?? 'Badge')) ?></td>
        <td width="90" align="right" valign="top" style="width:90px; padding:14px 0; border-bottom:1px solid #e8eded; color:#66747b; font-size:13px; font-weight:400; line-height:1.45; white-space:nowrap;">Quantity <?= (int)$line->quantity ?></td>
    </tr>
<?php endforeach; ?>
<?php endforeach; ?>
    <tr><td style="padding:20px 12px 0 0; color:#66747b; font-size:14px; font-weight:800;">Total</td><td align="right" style="padding:20px 0 0; color:#172329; font-size:22px; font-weight:900; white-space:nowrap;"><?= (int)$fulfilment->total_quantity ?> <?= (int)$fulfilment->total_quantity === 1 ? 'badge' : 'badges' ?></td></tr>
</table>
<div style="margin-top:32px; padding:20px; border-left:4px solid #088486; border-radius:0 10px 10px 0; background:#e5f5f3; color:#05696c; font-size:14px; line-height:1.6;"><strong style="color:#0c3436;">What happens next?</strong><br><?= h($emailCopy['next']) ?></div>
