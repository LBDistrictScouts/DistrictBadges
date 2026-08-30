<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Invoice Entity
 *
 * @property string $id
 * @property \Cake\I18n\DateTime $invoice_date
 * @property \Cake\I18n\DateTime $due_date
 * @property \Cake\I18n\Date|null $period_start_date
 * @property \Cake\I18n\Date|null $period_end_date
 * @property string $invoice_number
 * @property string $account_id
 * @property string $total_amount
 * @property \Cake\I18n\DateTime|null $last_downloaded
 *
 * @property \App\Model\Entity\Account $account
 * @property \App\Model\Entity\InvoiceSummary[] $invoice_summaries
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
        'period_start_date' => true,
        'period_end_date' => true,
        'account_id' => true,
        'total_amount' => false,
        'last_downloaded' => false,
        'account' => true,
        'invoice_summaries' => true,
    ];
}
