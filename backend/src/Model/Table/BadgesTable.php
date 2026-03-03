<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Service\AlgoliaService;
use App\Service\NationalShopService;
use ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\Log\Log;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use RuntimeException;

/**
 * Badges Model
 *
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
        if (!empty($options['skipNationalData'])) {
            return;
        }

        if (!$entity->isDirty('national_product_code')) {
            return;
        }

        $this->populateNationalData($entity);
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
