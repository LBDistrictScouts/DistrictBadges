<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Order Entity
 *
 * @property string $id
 * @property string $order_number
 * @property \Cake\I18n\DateTime $placed_date
 * @property \App\Model\Enum\OrderStatus $status
 * @property bool $fulfilled
 * @property string $total_ordered_amount
 * @property int $total_ordered_quantity
 * @property string $total_fulfilled_amount
 * @property int $total_fulfilled_quantity
 * @property string $account_id
 * @property string $user_id
 * @property string|null $section_id
 * @property string $idempotency_key
 * @property string|null $request_fingerprint
 * @property string|null $contact_first_name
 * @property string|null $contact_last_name
 * @property string|null $contact_email
 * @property \Cake\I18n\DateTime|null $last_notification_sent_at
 *
 * @property \App\Model\Entity\Account $account
 * @property \App\Model\Entity\User $user
 * @property \App\Model\Entity\Section|null $section
 * @property \App\Model\Entity\OrderLine[] $order_lines
 */
class Order extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'fulfilled' => false,
        'account_id' => true,
        'user_id' => true,
        'section_id' => true,
        'idempotency_key' => true,
        'request_fingerprint' => true,
        'contact_first_name' => true,
        'contact_last_name' => true,
        'contact_email' => true,
        'account' => true,
        'user' => true,
        'section' => true,
        'order_lines' => true,
    ];
}
