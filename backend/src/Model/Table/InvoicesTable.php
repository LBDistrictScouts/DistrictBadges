<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\Invoice;
use App\Model\Enum\FulfilmentStatus;
use ArrayObject;
use Cake\Core\Configure;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\I18n\DateTime;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use DateTimeImmutable;
use DateTimeInterface;
use DomainException;
use InvalidArgumentException;

/**
 * Invoices Model
 *
 * @property \App\Model\Table\AccountsTable&\Cake\ORM\Association\BelongsTo $Accounts
 * @property \App\Model\Table\InvoiceSummariesTable&\Cake\ORM\Association\HasMany $InvoiceSummaries
 * @method \App\Model\Entity\Invoice newEmptyEntity()
 * @method \App\Model\Entity\Invoice newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\Invoice> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Invoice get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Invoice findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\Invoice patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\Invoice> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Invoice|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Invoice saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\Invoice>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Invoice>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Invoice>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Invoice> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Invoice>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Invoice>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Invoice>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Invoice> deleteManyOrFail(iterable $entities, array $options = [])
 */
class InvoicesTable extends Table
{
    private const PAYMENT_TERMS_DAYS = 30;
    private const FIRST_BILLING_DATE = '2026-01-01';

    /**
     * Initialize method
     *
     * @param array<string, mixed> $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('invoices');
        $this->setDisplayField('invoice_number');
        $this->setPrimaryKey('id');

        $this->addBehavior('EntityNumber', [
            'field' => 'invoice_number',
            'prefix' => Configure::read('EntityNumbers.invoicePrefix', 'INV'),
        ]);

        $this->belongsTo('Accounts', [
            'foreignKey' => 'account_id',
            'joinType' => 'INNER',
        ]);
        $this->hasMany('InvoiceSummaries', [
            'foreignKey' => 'invoice_id',
            'saveStrategy' => 'replace',
            'finder' => 'ordered',
        ]);
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->dateTime('invoice_date')
            ->requirePresence('invoice_date', 'create')
            ->notEmptyDateTime('invoice_date');

        $validator
            ->dateTime('due_date')
            ->requirePresence('due_date', 'create')
            ->notEmptyDateTime('due_date');

        $validator
            ->date('period_start_date')
            ->allowEmptyDate('period_start_date');

        $validator
            ->date('period_end_date')
            ->allowEmptyDate('period_end_date');

        $validator
            ->scalar('invoice_number')
            ->maxLength('invoice_number', 255)
            ->allowEmptyString('invoice_number');

        $validator
            ->uuid('account_id')
            ->notEmptyString('account_id');

        $validator
            ->decimal('total_amount')
            ->notEmptyString('total_amount');

        $validator
            ->dateTime('last_downloaded')
            ->allowEmptyDateTime('last_downloaded');

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn(['account_id'], 'Accounts'), ['errorField' => 'account_id']);

        return $rules;
    }

    /**
     * Build the supported subset of an invoice-generator.com JSON payload.
     *
     * @param string $invoiceId Invoice id.
     * @return array<string, mixed>
     */
    public function toInvoiceGeneratorData(string $invoiceId): array
    {
        $invoice = $this->get($invoiceId, contain: [
            'Accounts',
            'InvoiceSummaries' => [
                'Orders' => ['Users', 'Sections'],
                'Fulfilments',
                'InvoiceLines',
            ],
        ]);

        $items = [];
        foreach ($invoice->invoice_summaries as $summary) {
            $postage = 0.0;
            foreach ($summary->invoice_lines as $line) {
                if ($line->badge_id === null) {
                    $postage += (float)$line->line_amount;
                }
            }
            $description = sprintf('%d badges', $summary->quantity);
            if ($postage > 0) {
                $description .= sprintf(' + £%.2f postage', $postage);
            }
            $description .= sprintf(
                '. Ordered by: %s. Section: %s.',
                $summary->order->user->full_name,
                $summary->order->section?->section_name ?? 'Not specified',
            );
            $items[] = [
                'name' => sprintf(
                    'Order %s / Fulfilment %s',
                    $summary->order->order_number,
                    $summary->fulfilment->fulfilment_number,
                ),
                'description' => $description,
                'quantity' => 1,
                'unit_cost' => (float)$summary->line_amount,
            ];
        }

        return [
            'type' => 'invoice',
            'from' => (string)Configure::read(
                'InvoiceGenerator.from',
                'LBA Scouts District Badge Shop',
            ),
            'to' => $invoice->account->account_name,
            'number' => $invoice->invoice_number,
            'date' => $invoice->invoice_date->format('Y-m-d'),
            'due_date' => $invoice->due_date->format('Y-m-d'),
            'currency' => 'GBP',
            'items' => $items,
        ];
    }

    /**
     * Generate the previous month's outstanding invoices for every account.
     *
     * @param \DateTimeInterface $runDate Date on which the batch is run.
     * @param float $minimumTotal Invoice total must exceed this amount.
     * @return array{generated: int, skipped: int, messages: array<string>}
     */
    public function generateMonthly(DateTimeInterface $runDate, float $minimumTotal): array
    {
        $periodEnd = new DateTimeImmutable($runDate->format('Y-m-01 00:00:00'));
        $periodEnd = $periodEnd->modify('-1 second');
        $generated = 0;
        $skipped = 0;
        $messages = [];

        foreach ($this->Accounts->find()->all() as $account) {
            $lastInvoice = $this->find()
                ->where([
                    'account_id' => $account->id,
                    'period_end_date IS NOT' => null,
                ])
                ->orderByDesc('period_end_date')
                ->first();
            if (
                $lastInvoice !== null
                && $lastInvoice->period_end_date->format('Y-m-d') >= $periodEnd->format('Y-m-d')
            ) {
                $skipped++;
                continue;
            }

            $periodStart = $lastInvoice === null
                ? new DateTimeImmutable(self::FIRST_BILLING_DATE)
                : new DateTimeImmutable($lastInvoice->period_end_date->addDays(1)->format('Y-m-d'));
            if ($periodStart > $periodEnd) {
                $skipped++;
                continue;
            }

            try {
                $invoice = $this->generate($periodStart, $periodEnd, $account->id, $minimumTotal);
                $invoice = $this->get($invoice->id);
                $messages[] = sprintf(
                    'Generated %s for %s (£%.2f).',
                    $invoice->invoice_number,
                    $account->account_name,
                    (float)$invoice->total_amount,
                );
                $generated++;
            } catch (DomainException $exception) {
                $messages[] = sprintf('Skipped %s: %s', $account->account_name, $exception->getMessage());
                $skipped++;
            }
        }

        return compact('generated', 'skipped', 'messages');
    }

    /**
     * Generate an invoice from dispatched badge fulfilments for one account.
     *
     * The date range is inclusive. The invoice is summarised by order and
     * fulfilment, with badge/price detail retained beneath each summary.
     *
     * @param \DateTimeInterface $startDate First dispatched date to include.
     * @param \DateTimeInterface $endDate Last dispatched date to include.
     * @param string $accountId Account being invoiced.
     * @param float $minimumTotal Invoice total must exceed this amount.
     * @return \App\Model\Entity\Invoice
     */
    public function generate(
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
        string $accountId,
        float $minimumTotal = 0.0,
    ): Invoice {
        $start = new DateTimeImmutable($startDate->format('Y-m-d 00:00:00'));
        $end = new DateTimeImmutable($endDate->format('Y-m-d 23:59:59'));
        if ($start > $end) {
            throw new InvalidArgumentException('The invoice start date must be on or before the end date.');
        }
        $today = new DateTimeImmutable('today');
        if ($end >= $today) {
            throw new InvalidArgumentException('The invoice end date must be before today.');
        }
        if (!$this->Accounts->exists(['id' => $accountId])) {
            throw new InvalidArgumentException('The selected account does not exist.');
        }

        $fulfilmentLines = TableRegistry::getTableLocator()->get('FulfilmentLines');
        $alreadyInvoiced = $this->InvoiceSummaries->find()
            ->select(['fulfilment_id'])
            ->where(['fulfilment_id IS NOT' => null]);
        $billableLines = $fulfilmentLines->find()
            ->contain(['Badges', 'OrderLines', 'Fulfilments'])
            ->innerJoinWith('OrderLines.Orders')
            ->where([
                'Fulfilments.status' => FulfilmentStatus::Dispatched->value,
                'Fulfilments.dispatched_date >=' => $start,
                'Fulfilments.dispatched_date <=' => $end,
                'Orders.account_id' => $accountId,
                'Fulfilments.id NOT IN' => $alreadyInvoiced,
            ])
            ->all();

        $summaries = [];
        $fulfilmentPostage = [];
        foreach ($billableLines as $line) {
            $unitPrice = number_format((float)$line->unit_price, 2, '.', '');
            $orderId = (string)$line->order_line->order_id;
            $fulfilmentId = (string)$line->fulfilment_id;
            $summaryKey = $orderId . ':' . $fulfilmentId;
            $detailKey = (string)$line->badge_id . ':' . $unitPrice;
            if (!isset($fulfilmentPostage[$fulfilmentId])) {
                $fulfilmentPostage[$fulfilmentId] = [
                    'summary_key' => $summaryKey,
                    'amount' => (float)$line->fulfilment->postage_charge,
                ];
            }
            if (!isset($summaries[$summaryKey])) {
                $summaries[$summaryKey] = [
                    'order_id' => $orderId,
                    'fulfilment_id' => $fulfilmentId,
                    'quantity' => 0,
                    'line_amount' => 0.0,
                    'invoice_lines' => [],
                ];
            }
            if (!isset($summaries[$summaryKey]['invoice_lines'][$detailKey])) {
                $summaries[$summaryKey]['invoice_lines'][$detailKey] = [
                    'badge_id' => $line->badge_id,
                    'description' => $line->badge->badge_name,
                    'quantity' => 0,
                    'unit_price' => $unitPrice,
                    'line_amount' => 0.0,
                ];
            }
            $quantity = (int)$line->fulfilled_quantity_change;
            $amount = (float)$line->monetary_amount;
            $summaries[$summaryKey]['quantity'] += $quantity;
            $summaries[$summaryKey]['line_amount'] += $amount;
            $summaries[$summaryKey]['invoice_lines'][$detailKey]['quantity'] += $quantity;
            $summaries[$summaryKey]['invoice_lines'][$detailKey]['line_amount'] += $amount;
        }
        foreach ($fulfilmentPostage as $fulfilmentId => $postage) {
            if ($postage['amount'] <= 0) {
                continue;
            }
            $summaryKey = $postage['summary_key'];
            $summaries[$summaryKey]['line_amount'] += $postage['amount'];
            $summaries[$summaryKey]['invoice_lines']['postage:' . $fulfilmentId] = [
                'badge_id' => null,
                'description' => 'Postage',
                'quantity' => 1,
                'unit_price' => number_format($postage['amount'], 2, '.', ''),
                'line_amount' => $postage['amount'],
            ];
        }
        if ($summaries === []) {
            throw new DomainException('No dispatched badges were found for this account and date range.');
        }
        $invoiceTotal = array_sum(array_column($summaries, 'line_amount'));
        if ($invoiceTotal <= $minimumTotal) {
            throw new DomainException(sprintf(
                'The invoice total of £%.2f does not exceed the £%.2f minimum.',
                $invoiceTotal,
                $minimumTotal,
            ));
        }

        $summaryData = array_map(static function (array $summary): array {
            $summary['line_amount'] = number_format($summary['line_amount'], 2, '.', '');
            $summary['invoice_lines'] = array_map(static function (array $line): array {
                $line['line_amount'] = number_format($line['line_amount'], 2, '.', '');

                return $line;
            }, array_values($summary['invoice_lines']));

            return $summary;
        }, array_values($summaries));

        $now = DateTime::now();
        $invoice = $this->newEntity([
            'invoice_date' => $now,
            'due_date' => (clone $now)->addDays(self::PAYMENT_TERMS_DAYS),
            'period_start_date' => $start,
            'period_end_date' => $end,
            'account_id' => $accountId,
            'invoice_summaries' => $summaryData,
        ], ['associated' => ['InvoiceSummaries.InvoiceLines']]);

        return $this->getConnection()->transactional(function () use ($invoice): Invoice {
            return $this->saveOrFail($invoice, ['associated' => ['InvoiceSummaries']]);
        });
    }

    /**
     * Capture badge ids before database cascades remove the invoice lines.
     */
    public function beforeDelete(EventInterface $event, EntityInterface $entity, ArrayObject $options): void
    {
        $badgeIds = $this->InvoiceSummaries->InvoiceLines->find()
            ->select(['badge_id'])
            ->innerJoinWith('InvoiceSummaries')
            ->where([
                'InvoiceSummaries.invoice_id' => $entity->get('id'),
                'badge_id IS NOT' => null,
            ])
            ->distinct(['badge_id'])
            ->disableHydration()
            ->all()
            ->extract('badge_id')
            ->toList();
        $options['affectedBadgeIds'] = $badgeIds;
    }

    /**
     * Refresh cached quantities after the invoice and its lines are gone.
     */
    public function afterDelete(EventInterface $event, EntityInterface $entity, ArrayObject $options): void
    {
        foreach ($options['affectedBadgeIds'] ?? [] as $badgeId) {
            $this->InvoiceSummaries->InvoiceLines->refreshBadgeInvoicedQuantity((string)$badgeId);
        }
    }
}
