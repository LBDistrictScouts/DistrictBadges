<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Order $order Order with user, account, section, and order_lines.badge loaded
 */

$backendCreated = (bool)($this->get('backendCreated') ?? false);
$customerName = trim((string)($order->user->full_name ?? '')) ?: 'there';
$orderNumber = (string)$order->order_number;
$placedDate = $order->placed_date?->i18nFormat('d MMMM yyyy, HH:mm') ?? '';
$accountName = (string)($order->account->account_name ?? '');
$sectionName = (string)($order->section->section_name ?? '');
$orderFor = $sectionName !== '' ? $sectionName : $accountName;
$orderForContext = $sectionName !== '' && $accountName !== '' ? ' · ' . $accountName : '';
$totalQuantity = (int)$order->total_ordered_quantity;
$this->assign('title', 'We have received order ' . $orderNumber);
$this->assign('preheader', $backendCreated
    ? 'The district badge shop team has created an order for you.'
    : 'Your district badge order has been received and is now with our team.');
?>
<div style="margin:0 0 13px; color:#25b755; font-size:12px; font-weight:900; letter-spacing:1.8px; line-height:1.3; text-transform:uppercase;"><?= $backendCreated ? 'Order created' : 'Order received' ?></div>
<h1 class="email-heading" style="margin:0; color:#172329; font-size:40px; font-weight:900; letter-spacing:-1.5px; line-height:1.08;">Hi, <?= h($customerName) ?>.<br><span style="color:#7413dc;"><?= $backendCreated ? 'We’ve created an order for you.' : 'We have your order.' ?></span></h1>
<p style="margin:22px 0 28px; color:#66747b; font-size:16px; line-height:1.65;"><?= $backendCreated ? 'The district badge shop team has placed the order below on your behalf. We’ll email you again when it is ready to collect.' : 'Your badge order has reached the district team. We’ll prepare it and email you again when it is ready to collect.' ?></p>
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%; margin:0 0 30px; border-collapse:collapse; border-radius:12px; background:#f5f8f8;">
    <tr>
        <td class="order-meta-cell" width="50%" valign="top" style="width:50%; padding:20px 12px 20px 20px;"><div style="margin-bottom:5px; color:#66747b; font-size:10px; font-weight:900; letter-spacing:1px; text-transform:uppercase;">Order number</div><div style="color:#172329; font-size:15px; font-weight:900;"><?= h($orderNumber) ?></div></td>
        <td class="order-meta-cell" width="50%" valign="top" style="width:50%; padding:20px 20px 20px 12px;"><div style="margin-bottom:5px; color:#66747b; font-size:10px; font-weight:900; letter-spacing:1px; text-transform:uppercase;">Placed</div><div style="color:#172329; font-size:15px; font-weight:900;"><?= h($placedDate) ?></div></td>
    </tr>
<?php if ($accountName !== '' || $sectionName !== '') : ?>
    <tr><td colspan="2" style="padding:0 20px 20px;"><div style="padding-top:16px; border-top:1px solid #dfe7e7; color:#66747b; font-size:13px; line-height:1.5;">Ordered for <strong style="color:#172329;"><?= h($orderFor) ?></strong><?= h($orderForContext) ?></div></td></tr>
<?php endif; ?>
</table>
<h2 style="margin:0 0 14px; color:#172329; font-size:20px; font-weight:900; line-height:1.3;">Order summary</h2>
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%; border-collapse:collapse;">
<?php foreach ($order->order_lines as $line) : ?>
    <tr>
        <td valign="top" style="padding:14px 12px 14px 0; border-bottom:1px solid #dfe7e7; color:#172329; font-size:14px; line-height:1.45;"><strong style="font-weight:900;"><?= h((string)($line->badge->badge_name ?? 'Badge')) ?></strong><div style="padding-top:3px; color:#66747b; font-size:12px;">Quantity <?= (int)$line->quantity ?> × £<?= number_format((float)$line->unit_price, 2) ?></div></td>
        <td class="item-price" width="90" align="right" valign="top" style="width:90px; padding:14px 0; border-bottom:1px solid #dfe7e7; color:#172329; font-size:14px; font-weight:900; line-height:1.45; white-space:nowrap;">£<?= number_format((float)$line->amount, 2) ?></td>
    </tr>
<?php endforeach; ?>
    <tr><td style="padding:20px 12px 0 0; color:#66747b; font-size:14px; font-weight:800;"><?= $totalQuantity ?> <?= $totalQuantity === 1 ? 'badge' : 'badges' ?></td><td align="right" style="padding:20px 0 0; color:#172329; font-size:22px; font-weight:900; white-space:nowrap;">£<?= number_format((float)$order->total_ordered_amount, 2) ?></td></tr>
</table>
<div style="margin-top:32px; padding:20px; border-left:4px solid #088486; border-radius:0 10px 10px 0; background:#e5f5f3; color:#05696c; font-size:14px; line-height:1.6;"><strong style="color:#0c3436;">What happens next?</strong><br>The district team will prepare your badges. You do not need to do anything—we’ll be in touch when your order is ready for collection.</div>
<p style="margin:28px 0 0; color:#66747b; font-size:13px; line-height:1.6;">Please keep this email for your records. Your Group Treasurer will be invoiced for badges that are fulfilled and collected.</p>
