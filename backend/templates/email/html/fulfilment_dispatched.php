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
$this->assign('title', 'Your badges are ready to collect');
$this->assign('preheader', 'Your district badge fulfilment has been prepared and is ready for collection.');
?>
<div style="margin:0 0 13px; color:#25b755; font-size:12px; font-weight:900; letter-spacing:1.8px; line-height:1.3; text-transform:uppercase;">Ready to collect</div>
<h1 class="email-heading" style="margin:0; color:#172329; font-size:40px; font-weight:900; letter-spacing:-1.5px; line-height:1.08;">Hi, <?= h($customerName) ?>.<br><span style="color:#7413dc;">Your badges are ready.</span></h1>
<p style="margin:22px 0 28px; color:#66747b; font-size:16px; line-height:1.65;">The district badge shop team has prepared the badges below. They are now ready for collection.</p>
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%; margin:0 0 30px; border-collapse:collapse; border-radius:12px; background:#f5f8f8;">
    <tr>
        <td width="50%" valign="top" style="width:50%; padding:20px 12px 20px 20px;"><div style="margin-bottom:5px; color:#66747b; font-size:10px; font-weight:900; letter-spacing:1px; text-transform:uppercase;">Fulfilment</div><div style="color:#172329; font-size:15px; font-weight:900;"><?= h((string)$fulfilment->fulfilment_number) ?></div></td>
        <td width="50%" valign="top" style="width:50%; padding:20px 20px 20px 12px;"><div style="margin-bottom:5px; color:#66747b; font-size:10px; font-weight:900; letter-spacing:1px; text-transform:uppercase;">Prepared</div><div style="color:#172329; font-size:15px; font-weight:900;"><?= h($dispatchedDate) ?></div></td>
    </tr>
<?php if ($orderNumbers !== []) : ?>
    <tr><td colspan="2" style="padding:0 20px 20px;"><div style="padding-top:16px; border-top:1px solid #dfe7e7; color:#66747b; font-size:13px; line-height:1.5;">For <?= count($orderNumbers) === 1 ? 'order' : 'orders' ?> <strong style="color:#172329;"><?= h(implode(', ', array_keys($orderNumbers))) ?></strong></div></td></tr>
<?php endif; ?>
</table>
<h2 style="margin:0 0 14px; color:#172329; font-size:20px; font-weight:900; line-height:1.3;">Collection summary</h2>
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
<div style="margin-top:32px; padding:20px; border-left:4px solid #088486; border-radius:0 10px 10px 0; background:#e5f5f3; color:#05696c; font-size:14px; line-height:1.6;"><strong style="color:#0c3436;">What happens next?</strong><br>Please arrange collection with the district badge shop team.</div>
