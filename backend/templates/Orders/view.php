<?php
use Cake\Core\Configure;

/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Order $order
 */
$dispatchAddress = array_filter([
    $order->dispatch_address_line_1,
    $order->dispatch_address_line_2,
    $order->dispatch_town,
    $order->dispatch_county,
    $order->dispatch_postcode,
]);
$postagePrice = $this->Number->currency((float)Configure::read('Postage.price', 0));
$multipleOrdersMessage = 'If the customer places multiple orders, they may be grouped into one dispatch '
    . 'and charged postage once.';
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(
                __('Edit Order'),
                ['action' => 'edit', $order->id],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->Form->postLink(
                __('Delete Order'),
                ['action' => 'delete', $order->id],
                [
                    'confirm' => __('Are you sure you want to delete this order?'),
                    'class' => 'side-nav-item',
                ],
            ) ?>
            <?= $this->Html->link(__('List Orders'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('New Order'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="orders view content">
            <h3><?= h($order->order_number) ?></h3>
            <table>
                <tr>
                    <th><?= __('Status') ?></th>
                    <td><?= h($order->status->label()) ?></td>
                </tr>
                <tr>
                    <th><?= __('Order for') ?></th>
                    <td>
                        <?php if ($order->account?->hasValue('group')) : ?>
                            <?= $this->Html->link(
                                $order->account->group->group_name,
                                ['controller' => 'Groups', 'action' => 'view', $order->account->group->id],
                            ) ?>
                            <span aria-hidden="true">›</span>
                        <?php endif; ?>
                        <?= $order->hasValue('account')
                            ? $this->Html->link(
                                $order->account->account_name,
                                ['controller' => 'Accounts', 'action' => 'view', $order->account->id],
                            )
                            : __('No account') ?>
                        <?php if ($order->hasValue('section')) : ?>
                            <span aria-hidden="true">›</span>
                            <?= $this->Html->link(
                                $order->section->section_name,
                                ['controller' => 'Sections', 'action' => 'view', $order->section->id],
                            ) ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th><?= __('User') ?></th>
                    <td>
                        <?= $order->hasValue('user')
                            ? $this->Html->link(
                                trim(sprintf(
                                    '%s %s',
                                    $order->contact_first_name ?? '',
                                    $order->contact_last_name ?? '',
                                )) ?: $order->user->full_name,
                                ['controller' => 'Users', 'action' => 'view', $order->user->id],
                            )
                            : __('No user') ?>
                        <?php if ($order->contact_email) : ?>
                            <br><?= h($order->contact_email) ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th><?= __('Placed') ?></th>
                    <td><?= h($order->placed_date?->i18nFormat('dd MMM yyyy HH:mm')) ?></td>
                </tr>
                <tr>
                    <th><?= __('Last Order Email Sent') ?></th>
                    <td>
                        <?= $order->last_notification_sent_at
                            ? h($order->last_notification_sent_at->i18nFormat('dd MMM yyyy HH:mm'))
                            : __('Not sent') ?>
                        <?= $this->Form->postLink(
                            __('Resend Order Email'),
                            ['action' => 'resendNotification', $order->id],
                            [
                                'confirm' => __('Resend the order notification email to this user?'),
                                'class' => 'button button-outline float-right',
                            ],
                        ) ?>
                    </td>
                </tr>
                <tr>
                    <th><?= __('Ordered Total') ?></th>
                    <td>
                        <?= $this->Number->format($order->total_ordered_quantity) ?>
                        <?= __('items') ?>,
                        <?= $this->Number->currency($order->total_ordered_amount) ?>
                    </td>
                </tr>
                <tr>
                    <th><?= __('Fulfilled Total') ?></th>
                    <td>
                        <?= $this->Number->format($order->total_fulfilled_quantity) ?>
                        <?= __('items') ?>,
                        <?= $this->Number->currency($order->total_fulfilled_amount) ?>
                    </td>
                </tr>
            </table>

            <div class="related">
                <h4><?= __('Delivery') ?></h4>
                <?php if ($order->postage === true) : ?>
                    <p style="margin-bottom:0.5rem;">
                        <strong>
                            <?= __('Postage selected ({0} per dispatch)', $postagePrice) ?>
                        </strong>
                        <button
                            type="button"
                            class="button button-clear"
                            id="postage-info-open"
                            aria-haspopup="dialog"
                            aria-controls="postage-info-dialog"
                        ><?= __('Postage information') ?></button>
                    </p>
                    <address style="margin-bottom:0;">
                        <?php foreach ($dispatchAddress as $addressLine) : ?>
                            <?= h($addressLine) ?><br>
                        <?php endforeach; ?>
                    </address>
                    <dialog id="postage-info-dialog" class="audit-count-dialog">
                        <div class="audit-count-dialog__heading">
                            <h4><?= __('Postage charges') ?></h4>
                            <button
                                type="button"
                                class="button button-clear audit-dialog-close"
                                aria-label="<?= __('Close') ?>"
                                id="postage-info-close"
                            >×</button>
                        </div>
                        <p>
                            <?= __('Postage is charged at {0} for each dispatch.', $postagePrice) ?>
                            <?= __('Back-ordered badges may require more than one dispatch and postage charge.') ?>
                            <?= __($multipleOrdersMessage) ?>
                            <?= __('The invoice will reflect the number of dispatches actually made.') ?>
                        </p>
                    </dialog>
                <?php else : ?>
                    <p>
                        <strong><?= __('Collection selected') ?></strong><br>
                        <?= __('This order will be prepared for collection from the district badge shop.') ?>
                    </p>
                <?php endif; ?>
            </div>

            <div class="related">
                <h4><?= __('Order Lines') ?></h4>
                <?php if (!empty($order->order_lines)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Badge') ?></th>
                            <th><?= __('Quantity') ?></th>
                            <th><?= __('Fulfilled') ?></th>
                            <th><?= __('Remaining') ?></th>
                            <th><?= __('Unit Price') ?></th>
                            <th><?= __('Line Amount') ?></th>
                            <th><?= __('Status') ?></th>
                        </tr>
                        <?php foreach ($order->order_lines as $line) : ?>
                        <tr>
                            <td>
                                <?= $line->hasValue('badge')
                                    ? $this->Html->link(
                                        $line->badge->badge_name,
                                        ['controller' => 'Badges', 'action' => 'view', $line->badge->id],
                                    )
                                    : __('Unknown badge') ?>
                            </td>
                            <td><?= $this->Number->format($line->quantity) ?></td>
                            <td><?= $this->Number->format($line->fulfilled_quantity) ?></td>
                            <td><?= $this->Number->format($line->remaining_quantity) ?></td>
                            <td><?= $this->Number->currency($line->unit_price) ?></td>
                            <td><?= $this->Number->currency($line->amount) ?></td>
                            <td>
                                <?php if ($line->fulfilled) : ?>
                                    <?= __('Fulfilled') ?>
                                <?php elseif ($line->fulfilled_quantity > 0) : ?>
                                    <?= __('Partially Fulfilled') ?>
                                <?php else : ?>
                                    <?= __('Open') ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                <?php else : ?>
                <p><?= __('No order lines have been added.') ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php if ($order->postage === true) : ?>
<script>
    (function () {
        var dialog = document.getElementById('postage-info-dialog');
        document.getElementById('postage-info-open').addEventListener('click', function () {
            dialog.showModal();
        });
        document.getElementById('postage-info-close').addEventListener('click', function () {
            dialog.close();
        });
        dialog.addEventListener('click', function (event) {
            if (event.target === dialog) {
                dialog.close();
            }
        });
    }());
</script>
<?php endif; ?>
