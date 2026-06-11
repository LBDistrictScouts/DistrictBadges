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
use Cake\ORM\Exception\PersistenceFailedException;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use RuntimeException;

/**
 * Badges Model
 *
 * @property \App\Model\Table\StockTransactionsTable&\Cake\ORM\Association\HasMany $StockTransactions
 * @property \App\Model\Table\InvoiceLinesTable&\Cake\ORM\Association\HasMany $InvoiceLines
 * @property \App\Model\Table\OrderLinesTable&\Cake\ORM\Association\HasMany $OrderLines
 * @property \App\Model\Table\BadgeTagsTable&\Cake\ORM\Association\BelongsToMany $BadgeTags
 * @property \App\Model\Table\BadgeSectionsTable&\Cake\ORM\Association\BelongsToMany $BadgeSections
 * @property \App\Model\Table\BadgeTypesTable&\Cake\ORM\Association\BelongsToMany $BadgeTypes
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
     * Associate every tag whose search text occurs in the badge name.
     *
     * A leading caret anchors the literal search text to the start of the name,
     * and a trailing dollar sign anchors it to the end.
     *
     * Existing associations are preserved and duplicate links are not created.
     *
     * @param \Cake\Datasource\EntityInterface $badge Persisted badge.
     * @param bool $syncAlgolia Refresh the badge search record after linking.
     * @return int Number of associations created.
     */
    public function associateTagsFromBadgeName(
        EntityInterface $badge,
        bool $syncAlgolia = true,
    ): int {
        $badgeId = (string)$badge->get('id');
        if ($badgeId === '') {
            throw new PersistenceFailedException($badge, 'Badge must be saved before parsing tags.');
        }

        $badgeName = (string)$badge->get('badge_name');
        if ($badgeName === '') {
            return 0;
        }

        $existingIds = $this->BadgesBadgeTags->find()
            ->select(['badge_tag_id'])
            ->where(['badge_id' => $badgeId])
            ->all()
            ->extract('badge_tag_id')
            ->map(static fn($id): string => (string)$id)
            ->toList();

        $matchingTags = [];
        foreach ($this->BadgeTags->find()->orderByAsc('tag_order')->orderByAsc('tag_name') as $tag) {
            $searchText = trim((string)$tag->tag_search_text);
            if (
                $this->badgeNameMatchesTagSearchText($badgeName, $searchText)
                && !in_array((string)$tag->id, $existingIds, true)
            ) {
                $matchingTags[] = $tag;
            }
        }

        if ($matchingTags === []) {
            if ($syncAlgolia) {
                $this->syncBadgeToAlgolia($badge);
            }

            return 0;
        }

        $this->getAssociation('BadgeTags')->link($badge, $matchingTags);
        if ($syncAlgolia) {
            $this->syncBadgeToAlgolia($badge);
        }

        return count($matchingTags);
    }

    /**
     * @param string $badgeName Badge name.
     * @param string $searchText Literal search text with optional ^ and $ anchors.
     * @return bool
     */
    private function badgeNameMatchesTagSearchText(string $badgeName, string $searchText): bool
    {
        if ($searchText === '') {
            return false;
        }

        $startAnchored = str_starts_with($searchText, '^');
        $endAnchored = str_ends_with($searchText, '$');

        if ($startAnchored) {
            $searchText = mb_substr($searchText, 1);
        }
        if ($endAnchored) {
            $searchText = mb_substr($searchText, 0, -1);
        }

        $searchText = trim($searchText);
        if ($searchText === '') {
            return false;
        }

        $badgeName = mb_strtolower($badgeName);
        $searchText = mb_strtolower($searchText);

        if ($startAnchored && !str_starts_with($badgeName, $searchText)) {
            return false;
        }
        if ($endAnchored && !str_ends_with($badgeName, $searchText)) {
            return false;
        }

        return $startAnchored || $endAnchored || str_contains($badgeName, $searchText);
    }

    /**
     * Parse and associate tags for every badge.
     *
     * @return array{badges: int, associations: int}
     */
    public function associateTagsForAllBadges(): array
    {
        $badgeCount = 0;
        $associationCount = 0;

        foreach ($this->find()->all() as $badge) {
            $badgeCount++;
            $associationCount += $this->associateTagsFromBadgeName($badge);
        }

        return [
            'badges' => $badgeCount,
            'associations' => $associationCount,
        ];
    }

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
        $this->belongsToMany('BadgeTags', [
            'joinTable' => 'badges_badge_tags',
            'foreignKey' => 'badge_id',
            'targetForeignKey' => 'badge_tag_id',
        ]);
        $this->belongsToMany('BadgeSections', [
            'joinTable' => 'badges_badge_tags',
            'foreignKey' => 'badge_id',
            'targetForeignKey' => 'badge_tag_id',
            'sort' => ['BadgeSections.tag_order' => 'ASC', 'BadgeSections.tag_name' => 'ASC'],
        ]);
        $this->belongsToMany('BadgeTypes', [
            'joinTable' => 'badges_badge_tags',
            'foreignKey' => 'badge_id',
            'targetForeignKey' => 'badge_tag_id',
            'sort' => ['BadgeTypes.tag_order' => 'ASC', 'BadgeTypes.tag_name' => 'ASC'],
        ]);
        $this->hasMany('BadgesBadgeTags', [
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
    public function afterSaveCommit(
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

            $this->syncBadgeToAlgolia($entity, $service);
        } catch (RuntimeException $exception) {
            Log::warning($exception->getMessage());
        }
    }

    /**
     * @param \Cake\Datasource\EntityInterface $badge Badge.
     * @param \App\Service\AlgoliaService|null $service Service override.
     * @return void
     */
    public function syncBadgeToAlgolia(
        EntityInterface $badge,
        ?AlgoliaService $service = null,
    ): void {
        if ($badge->get('status') === BadgeStatus::Unstocked) {
            return;
        }

        try {
            $badge = $this->get($badge->get('id'), contain: ['BadgeSections', 'BadgeTypes']);
            ($service ?? $this->buildAlgoliaService())->upsertBadge($badge);
        } catch (RuntimeException $exception) {
            Log::warning($exception->getMessage());
        }
    }

    /**
     * Replace the complete Algolia badge index with searchable badges.
     *
     * @param \App\Service\AlgoliaService|null $service Service override.
     * @return int Number of indexed badges.
     */
    public function refreshAlgoliaIndex(?AlgoliaService $service = null): int
    {
        $badges = $this->find()
            ->where(['status !=' => BadgeStatus::Unstocked->value])
            ->contain(['BadgeSections', 'BadgeTypes'])
            ->orderByAsc('badge_name');

        return ($service ?? $this->buildAlgoliaService())->replaceBadges($badges);
    }

    /**
     * @return \App\Service\AlgoliaService
     */
    protected function buildAlgoliaService(): AlgoliaService
    {
        return new AlgoliaService();
    }
}
