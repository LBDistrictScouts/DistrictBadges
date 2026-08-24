<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Order $order
 */

$backendCreated = (bool)($this->get('backendCreated') ?? false);
$customerName = trim(sprintf('%s %s', $order->contact_first_name ?? '', $order->contact_last_name ?? ''))
    ?: (trim((string)($order->user->full_name ?? '')) ?: 'there');
$placedDate = $order->placed_date?->i18nFormat('d MMMM yyyy, HH:mm') ?? '';
$totalQuantity = (int)$order->total_ordered_quantity;
?>
<?= $backendCreated ? 'ORDER CREATED' : 'ORDER RECEIVED' ?>

Hi, <?= $customerName ?>. <?= $backendCreated ? 'Our team has placed your order.' : 'We have your order.' ?>

<?= $backendCreated ? 'The district badge shop team has placed the order below on your behalf.' : 'Your badge order has reached the district team.' ?> We’ll email you again when it is ready to collect.

Order number: <?= $order->order_number ?>
Placed: <?= $placedDate ?>
<?php if (!empty($order->section?->section_name)) : ?>
Section: <?= $order->section->section_name ?>
<?php endif; ?>
<?php if (!empty($order->account?->account_name)) : ?>
Account: <?= $order->account->account_name ?>
<?php endif; ?>

ORDER SUMMARY
<?php foreach ($order->order_lines as $line) : ?>
- <?= $line->badge->badge_name ?? 'Badge' ?> — <?= (int)$line->quantity ?> × £<?= number_format((float)$line->unit_price, 2) ?> = £<?= number_format((float)$line->amount, 2) ?>
<?php endforeach; ?>

<?= $totalQuantity ?> <?= $totalQuantity === 1 ? 'badge' : 'badges' ?> — Total £<?= number_format((float)$order->total_ordered_amount, 2) ?>

WHAT HAPPENS NEXT?
The district team will prepare your badges. You do not need to do anything—we’ll be in touch when your order is ready for collection.

Please keep this email for your records. Your Group Treasurer will be invoiced for badges that are fulfilled and collected.

Letchworth, Baldock & Ashwell Scouts
Preparing young people with #SkillsForLife
Registered Charity 279860
