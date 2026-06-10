<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Enum\BadgeStatus;
use App\Model\Enum\OrderStatus;
use App\Service\AlgoliaService;
use App\Service\NationalShopService;
use ArrayObject;
use Cake\Database\Type\EnumType;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\Log\Log;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use RuntimeException;

/**
 * Badges Model
 *
 * @property \App\Model\Table\StockTransactionsTable&\Cake\ORM\Association\HasMany $StockTransactions
 * @property \App\Model\Table\InvoiceLinesTable&\Cake\ORM\Association\HasMany $InvoiceLines
 * @property \App\Model\Table\OrderLinesTable&\Cake\ORM\Association\HasMany $OrderLines
 * @method \App\Model\Entity\Badge newEmptyEntity()
 * @method \App\Model\Entity\Badge newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\Badge> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Badge get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Badge findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\Badge patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\Badge> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Badge|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Badge saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\Badge>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Badge>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Badge>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Badge> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Badge>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Badge>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Badge>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Badge> deleteManyOrFail(iterable $entities, array $options = [])
 */
class BadgesTable extends Table
{
    /**
     * Calculate positive replenishment requirements for actively stocked badges.
     *
     * @return array<string, array{badge: \App\Model\Entity\Badge, quantity: int}>
     */
    public function getReplenishmentRequirements(): array
    {
        $outstandingByBadge = [];
        $orderLines = $this->OrderLines->find()
            ->select(['badge_id', 'quantity', 'fulfilled_quantity'])
            ->innerJoinWith('Orders', function ($query) {
                return $query->where([
                    'Orders.status IN' => [
                        OrderStatus::Placed->value,
                        OrderStatus::PartiallyFulfilled->value,
                    ],
                ]);
            })
            ->all();

        foreach ($orderLines as $orderLine) {
            $badgeId = (string)$orderLine->badge_id;
            $outstanding = max(
                0,
                (int)$orderLine->quantity - (int)$orderLine->fulfilled_quantity,
            );
            $outstandingByBadge[$badgeId] = ($outstandingByBadge[$badgeId] ?? 0) + $outstanding;
        }

        $requirements = [];
        foreach ($this->find()->where(['stocked' => true])->orderBy(['badge_name' => 'ASC']) as $badge) {
            $required = (int)($outstandingByBadge[$badge->id] ?? 0)
                - (int)$badge->on_hand_quantity
                - (int)$badge->pending_quantity
                + (int)$badge->reserve_quantity;
            if ($required <= 0) {
                continue;
            }

            $requirements[(string)$badge->id] = [
                'badge' => $badge,
                'quantity' => $required,
            ];
        }

        return $requirements;
    }

    /**
     * Initialize method
     *
     * @param array<string, mixed> $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('badges');
        $this->setDisplayField('badge_name');
        $this->setPrimaryKey('id');
        $this->getSchema()->setColumnType('status', EnumType::from(BadgeStatus::class));

        $this->hasMany('StockTransactions', [
            'foreignKey' => 'badge_id',
        ]);
        $this->hasMany('InvoiceLines', [
            'foreignKey' => 'badge_id',
        ]);
        $this->hasMany('OrderLines', [
            'foreignKey' => 'badge_id',
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
            ->scalar('badge_name')
            ->maxLength('badge_name', 255)
            ->requirePresence('badge_name', 'create')
            ->notEmptyString('badge_name');

        $validator
            ->integer('national_product_code')
            ->allowEmptyString('national_product_code');

        $validator
            ->allowEmptyString('national_data');

        $validator
            ->boolean('stocked')
            ->requirePresence('stocked', 'create')
            ->notEmptyString('stocked');

        $validator
            ->integer('status')
            ->inList('status', array_column(BadgeStatus::cases(), 'value'))
            ->allowEmptyString('status');

        $validator
            ->integer('fulfilled_quantity')
            ->notEmptyString('fulfilled_quantity');

        $validator
            ->integer('reserve_quantity')
            ->greaterThanOrEqual('reserve_quantity', 0)
            ->notEmptyString('reserve_quantity');

        $validator
            ->decimal('replenishment_price')
            ->allowEmptyString('replenishment_price');

        return $validator;
    }

    /**
     * @param \Cake\Event\EventInterface $event Event.
     * @param \Cake\Datasource\EntityInterface $entity Entity.
     * @param \ArrayObject $options Options.
     * @return void
     */
    public function beforeSave(
        EventInterface $event,
        EntityInterface $entity,
        ArrayObject $options,
    ): void {
        $entity->set('status', $this->statusFromStock(
            (bool)$entity->get('stocked'),
            (int)$entity->get('on_hand_quantity'),
            (int)$entity->get('pending_quantity'),
        ));

        if (!empty($options['skipNationalData'])) {
            return;
        }

        if (!$entity->isDirty('national_product_code')) {
            return;
        }

        $this->populateNationalData($entity);
    }

    /**
     * @param bool $stocked Whether the badge is actively stocked.
     * @param int $onHandQuantity Current on-hand quantity.
     * @param int $pendingQuantity Current pending quantity.
     * @return \App\Model\Enum\BadgeStatus
     */
    private function statusFromStock(
        bool $stocked,
        int $onHandQuantity,
        int $pendingQuantity,
    ): BadgeStatus {
        return match (true) {
            !$stocked && $onHandQuantity <= 0 && $pendingQuantity <= 0 => BadgeStatus::Unstocked,
            !$stocked && ($onHandQuantity > 0 || $pendingQuantity > 0) => BadgeStatus::Deprecated,
            $onHandQuantity > 0 => BadgeStatus::Available,
            $pendingQuantity > 0 => BadgeStatus::OnBackOrder,
            default => BadgeStatus::Unavailable,
        };
    }

    /**
     * @param \Cake\Datasource\EntityInterface $entity Entity.
     * @param bool $force Force refresh.
     * @return void
     */
    public function populateNationalData(EntityInterface $entity, bool $force = false): void
    {
        if (!$force && !$entity->isDirty('national_product_code')) {
            return;
        }

        $productId = $entity->get('national_product_code');
        if ($productId === null) {
            return;
        }

        $service = new NationalShopService();
        $entity->set('national_data', $service->fetchProductByExternalId((int)$productId));
    }

    /**
     * @return void
     */
    public function refreshAllNationalData(): void
    {
        $query = $this->find()
            ->where(['national_product_code IS NOT' => null]);

        foreach ($query as $badge) {
            $this->populateNationalData($badge, true);
            $this->saveOrFail($badge);
        }
    }

    /**
     * @param \Cake\Event\EventInterface $event Event.
     * @param \Cake\Datasource\EntityInterface $entity Entity.
     * @param \ArrayObject $options Options.
     * @return void
     */
    public function afterSave(
        EventInterface $event,
        EntityInterface $entity,
        ArrayObject $options,
    ): void {
        if (!empty($options['skipAlgolia'])) {
            return;
        }

        $service = $this->buildAlgoliaService();

        try {
            if ($entity->get('status') === BadgeStatus::Unstocked) {
                $previousStatus = $entity->getOriginal('status');
                $wasSearchable = in_array(
                    $previousStatus,
                    [BadgeStatus::Deprecated, BadgeStatus::Unavailable],
                    true,
                );
                if ($wasSearchable) {
                    $service->deleteBadge($entity);
                }

                return;
            }

            $service->upsertBadge($entity);
        } catch (RuntimeException $exception) {
            Log::warning($exception->getMessage());
        }
    }

    /**
     * @return \App\Service\AlgoliaService
     */
    protected function buildAlgoliaService(): AlgoliaService
    {
        return new AlgoliaService();
    }
}
