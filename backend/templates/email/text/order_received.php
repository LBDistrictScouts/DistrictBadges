<?php
use Cake\Core\Configure;

/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Order $order
 */

$backendCreated = (bool)($this->get('backendCreated') ?? false);
$customerName = trim(sprintf('%s %s', $order->contact_first_name ?? '', $order->contact_last_name ?? ''))
    ?: (trim((string)($order->user->full_name ?? '')) ?: 'there');
$placedDate = $order->placed_date?->i18nFormat('d MMMM yyyy, HH:mm') ?? '';
$totalQuantity = (int)$order->total_ordered_quantity;
$postage = $order->postage === true;
$postagePrice = '£' . number_format((float)Configure::read('Postage.price', 0), 2);
$dispatchAddress = array_values(array_filter([
    trim((string)$order->dispatch_address_line_1),
    trim((string)$order->dispatch_address_line_2),
    trim((string)$order->dispatch_town),
    trim((string)$order->dispatch_county),
    trim((string)$order->dispatch_postcode),
], static fn(string $line): bool => $line !== ''));
?>
<?= $backendCreated ? 'ORDER CREATED' : 'ORDER RECEIVED' ?>

Hi, <?= $customerName ?>. <?= $backendCreated ? 'Our team has placed your order.' : 'We have your order.' ?>

<?= $backendCreated ? 'The district badge shop team has placed the order below on your behalf.' : 'Your badge order has reached the district team.' ?> We’ll email you again when it is ready <?= $postage ? 'to be posted.' : 'to collect.' ?>

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

<?php if ($postage) : ?>
POSTAGE SELECTED (<?= $postagePrice ?> PER DISPATCH)
    <?php foreach ($dispatchAddress as $line) : ?>
        <?= $line ?>
    <?php endforeach; ?>

Postage is charged for each dispatch. Back-ordered badges may require more than one dispatch and postage charge. If you place multiple orders, we may group them into one dispatch and charge postage once. Your invoice will reflect the number of dispatches actually made.
<?php else : ?>
COLLECTION SELECTED
The district team will prepare your badges for collection. You do not need to do anything—we’ll be in touch when your order is ready.
<?php endif; ?>

Please keep this email for your records. Your Group Treasurer will be invoiced for <?= $postage ? 'badges that are fulfilled and postage for each dispatch made.' : 'badges that are fulfilled and collected.' ?>

Letchworth, Baldock & Ashwell Scouts
Preparing young people with #SkillsForLife
Registered Charity 279860
