<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Invoice Entity
 *
 * @property int $id
 * @property \Cake\I18n\DateTime $invoice_date
 * @property \Cake\I18n\DateTime $due_date
 * @property string $invoice_number
 * @property string $account_id
 *
 * @property \App\Model\Entity\Account $account
 */
class Invoice extends Entity
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
        'invoice_date' => true,
        'due_date' => true,
        'invoice_number' => true,
        'account_id' => true,
        'account' => true,
    ];
}
