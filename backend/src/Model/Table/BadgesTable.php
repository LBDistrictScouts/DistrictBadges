<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Enum\BadgeStatus;
use App\Model\Enum\OrderStatus;
use App\Service\AlgoliaService;
use App\Service\NationalShopService;
use App\Utility\Value;
use ArrayObject;
use Cake\Database\Type\EnumType;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\Log\Log;
use Cake\ORM\Exception\PersistenceFailedException;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use RuntimeException;

/**
 * Badges Model
 *
 * @property \Cake\ORM\Association\HasMany<\App\Model\Table\StockTransactionsTable> $StockTransactions
 * @property \Cake\ORM\Association\HasMany<\App\Model\Table\InvoiceLinesTable> $InvoiceLines
 * @property \Cake\ORM\Association\HasMany<\App\Model\Table\OrderLinesTable> $OrderLines
 * @property \Cake\ORM\Association\BelongsToMany<\App\Model\Table\BadgeTagsTable> $BadgeTags
 * @property \Cake\ORM\Association\BelongsToMany<\App\Model\Table\BadgeSectionsTable> $BadgeSections
 * @property \Cake\ORM\Association\BelongsToMany<\App\Model\Table\BadgeTypesTable> $BadgeTypes
 * @property \Cake\ORM\Association\HasMany<\App\Model\Table\BadgesBadgeTagsTable> $BadgesBadgeTags
 * @extends \Cake\ORM\Table<array<string, \Cake\ORM\Behavior>, \App\Model\Entity\Badge>
 */
class BadgesTable extends Table
{
    use TypedTableLocatorTrait;

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
        $badgeId = Value::string($badge->get('id'));
        if ($badgeId === '') {
            throw new PersistenceFailedException($badge, 'Badge must be saved before parsing tags.');
        }

        $badgeName = Value::string($badge->get('badge_name'));
        if ($badgeName === '') {
            return 0;
        }

        $existingIds = $this->associatedQuery(
            $this->BadgesBadgeTags,
            $this->typedAssociation($this->BadgesBadgeTags, BadgesBadgeTagsTable::class),
        )
            ->select(['badge_tag_id'])
            ->where(['badge_id' => $badgeId])
            ->all()
            ->extract('badge_tag_id')
            ->map(static fn($id): string => Value::string($id))
            ->toList();

        $matchingTags = [];
        foreach (
            $this->associatedQuery(
                $this->BadgeTags,
                $this->typedAssociation($this->BadgeTags, BadgeTagsTable::class),
            )->orderByAsc(
                'tag_order',
            )->orderByAsc(
                'tag_name',
            ) as $tag
        ) {
            $searchText = trim(Value::string($tag->tag_search_text));
            if (
                $this->badgeNameMatchesTagSearchText($badgeName, $searchText)
                && !in_array(Value::string($tag->id), $existingIds, true)
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

        $this->BadgeTags->link($badge, $matchingTags);
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
        $orderLines = $this->associatedQuery(
            $this->OrderLines,
            $this->typedAssociation($this->OrderLines, OrderLinesTable::class),
        )
            ->select(['badge_id', 'quantity', 'fulfilled_quantity'])
            ->innerJoinWith('Orders', function (SelectQuery $query) {
                return $query->where([
                    'Orders.status IN' => [
                        OrderStatus::Placed->value,
                        OrderStatus::PartiallyFulfilled->value,
                    ],
                ]);
            })
            ->all();

        foreach ($orderLines as $orderLine) {
            $badgeId = Value::string($orderLine->badge_id);
            $outstanding = max(
                0,
                Value::int($orderLine->quantity) - Value::int($orderLine->fulfilled_quantity),
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
        $this->addBehavior('UserTracking');
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
            ->scalar('image_url')
            ->maxLength('image_url', 2048)
            ->url('image_url')
            ->allowEmptyString('image_url');

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
            ->integer('invoiced_quantity')
            ->notEmptyString('invoiced_quantity');

        $validator
            ->scalar('national_product_hash')
            ->maxLength('national_product_hash', 64)
            ->allowEmptyString('national_product_hash');

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
     * @param \Cake\Event\EventInterface<\Cake\ORM\Table> $event Event.
     * @param \Cake\Datasource\EntityInterface $entity Entity.
     * @param \ArrayObject<string, mixed> $options Options.
     * @return void
     */
    public function beforeSave(
        EventInterface $event,
        EntityInterface $entity,
        ArrayObject $options,
    ): void {
        $entity->set('status', $this->statusFromStock(
            (bool)$entity->get('stocked'),
            Value::int($entity->get('on_hand_quantity')),
            Value::int($entity->get('pending_quantity')),
        ));

        if (($options['skipNationalData'] ?? false) === true) {
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
        $entity->set('national_data', $service->fetchProductByExternalId(Value::int($productId)));
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
     * @param \Cake\Event\EventInterface<\Cake\ORM\Table> $event Event.
     * @param \Cake\Datasource\EntityInterface $entity Entity.
     * @param \ArrayObject<string, mixed> $options Options.
     * @return void
     */
    public function afterSaveCommit(
        EventInterface $event,
        EntityInterface $entity,
        ArrayObject $options,
    ): void {
        if (($options['skipAlgolia'] ?? false) === true) {
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
