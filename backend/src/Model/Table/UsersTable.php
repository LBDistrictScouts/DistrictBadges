<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\User;
use ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Users Model
 *
 * @property \App\Model\Table\AccountsTable&\Cake\ORM\Association\BelongsTo $Accounts
 * @property \App\Model\Table\FulfilmentsTable&\Cake\ORM\Association\HasMany $Fulfilments
 * @property \App\Model\Table\OrdersTable&\Cake\ORM\Association\HasMany $Orders
 * @method \App\Model\Entity\User newEmptyEntity()
 * @method \App\Model\Entity\User newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\User> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\User get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\User findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\User patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\User> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\User|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\User saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\User>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\User>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\User>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\User> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\User>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\User>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\User>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\User> deleteManyOrFail(iterable $entities, array $options = [])
 */
class UsersTable extends Table
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

        $this->setTable('users');
        $this->setDisplayField('email');
        $this->setPrimaryKey('id');

        $this->belongsTo('Accounts', [
            'foreignKey' => 'account_id',
            'joinType' => 'INNER',
        ]);
        $this->hasMany('Orders', [
            'foreignKey' => 'user_id',
        ]);
        $this->hasMany('Fulfilments', ['foreignKey' => 'user_id']);
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
            ->scalar('first_name')
            ->maxLength('first_name', 255)
            ->requirePresence('first_name', 'create')
            ->notEmptyString('first_name');

        $validator
            ->scalar('last_name')
            ->maxLength('last_name', 255)
            ->requirePresence('last_name', 'create')
            ->notEmptyString('last_name');

        $validator
            ->uuid('account_id')
            ->notEmptyString('account_id');

        $validator
            ->email('email')
            ->requirePresence('email', 'create')
            ->notEmptyString('email');

        foreach (
            ['address_line_1', 'address_line_2', 'town', 'county', 'postcode'] as $field
        ) {
            $validator
                ->scalar($field)
                ->maxLength($field, $field === 'postcode' ? 10 : 255)
                ->allowEmptyString($field);
        }

        return $validator;
    }

    /**
     * Keep the stored email-domain flag in sync whenever a user is saved.
     *
     * @param \Cake\Event\EventInterface $event Event instance.
     * @param \Cake\Datasource\EntityInterface $entity User being saved.
     * @param \ArrayObject<string, mixed> $options Save options.
     * @return void
     */
    public function beforeSave(EventInterface $event, EntityInterface $entity, ArrayObject $options): void
    {
        if (!$entity instanceof User) {
            return;
        }

        $entity->set('non_district_email', $this->calculateNonDistrictEmail($entity));
    }

    /**
     * Recalculate and persist a user's email-domain flag.
     *
     * @param \App\Model\Entity\User $user User with its account and group loaded.
     * @return void
     */
    public function refreshNonDistrictEmail(User $user): void
    {
        $user->set('non_district_email', $this->calculateNonDistrictEmail($user));
        $this->saveOrFail($user, ['checkRules' => false, 'validate' => false]);
    }

    /**
     * @param \App\Model\Entity\User $user User to inspect.
     * @return bool
     */
    private function calculateNonDistrictEmail(User $user): bool
    {
        $account = $user->get('account');
        if ($account === null || (string)$account->id !== (string)$user->account_id) {
            $account = $this->Accounts->find()
                ->contain(['Groups'])
                ->where(['Accounts.id' => $user->account_id])
                ->first();
        }
        $domains = $account?->group?->domains;
        if (!is_array($domains) || $domains === []) {
            return false;
        }

        $emailDomain = strrchr(trim($user->email), '@');
        if ($emailDomain === false || $emailDomain === '@') {
            return false;
        }

        $emailDomain = strtolower(substr($emailDomain, 1));
        $domains = array_map(
            static fn(mixed $domain): string => strtolower(rtrim(ltrim(trim((string)$domain), '@'), '.')),
            $domains,
        );

        return !in_array($emailDomain, $domains, true);
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
        $rules->add($rules->isUnique(['email']), ['errorField' => 'email']);
        $rules->add($rules->existsIn(['account_id'], 'Accounts'), ['errorField' => 'account_id']);

        return $rules;
    }
}
