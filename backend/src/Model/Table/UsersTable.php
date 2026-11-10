<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\Account;
use App\Model\Entity\Group;
use App\Model\Entity\User;
use App\Utility\Value;
use ArrayObject;
use Cake\Database\Expression\FunctionExpression;
use Cake\Database\Expression\QueryExpression;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Users Model
 *
 * @property \Cake\ORM\Association\BelongsTo<\App\Model\Table\GroupsTable> $Groups
 * @property \Cake\ORM\Association\HasMany<\App\Model\Table\FulfilmentsTable> $Fulfilments
 * @property \Cake\ORM\Association\HasMany<\App\Model\Table\OrdersTable> $Orders
 * @extends \Cake\ORM\Table<array<string, \Cake\ORM\Behavior>, \App\Model\Entity\User>
 */
class UsersTable extends Table
{
    use TypedTableLocatorTrait;

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
        $this->addBehavior('UserTracking');

        $this->belongsTo('Groups', [
            'foreignKey' => 'group_id',
            'joinType' => 'INNER',
        ]);
        $this->addBehavior('CounterCache', [
            'Groups' => ['users_count'],
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
            ->uuid('group_id')
            ->notEmptyString('group_id');

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
     * @param \Cake\Event\EventInterface<\Cake\ORM\Table> $event Event instance.
     * @param \Cake\Datasource\EntityInterface $entity User being saved.
     * @param \ArrayObject<string, mixed> $options Save options.
     * @return void
     */
    public function beforeSave(EventInterface $event, EntityInterface $entity, ArrayObject $options): void
    {
        if (!$entity instanceof User) {
            return;
        }

        $entity->set('non_district_email', $this->isNonDistrictEmailForGroup(
            $entity->email,
            $this->groupFor($entity),
        ));
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
     * Find and link a pre-provisioned local user to a Cognito subject.
     *
     * Email is used only for the first link. Future sign-ins must present the
     * same Cognito subject, so a changed email cannot transfer an account.
     */
    public function findForCognitoIdentity(string $email, string $cognitoSub): ?User
    {
        $normalizedEmail = mb_strtolower(trim($email));
        if ($normalizedEmail === '' || $cognitoSub === '') {
            return null;
        }

        $linkedUserEntity = $this->find()
            ->contain(['Groups'])
            ->where(['Users.cognito_sub' => $cognitoSub])
            ->first();
        $linkedUser = $linkedUserEntity instanceof User ? $linkedUserEntity : null;
        if ($linkedUser !== null) {
            return $linkedUser->group !== null ? $linkedUser : null;
        }

        $matches = $this->find()
            ->contain(['Groups'])
            ->where(function (QueryExpression $expression, SelectQuery $query) use ($normalizedEmail) {
                return $expression->eq(
                    new FunctionExpression('LOWER', ['Users.email' => 'literal']),
                    $normalizedEmail,
                );
            })
            ->all()
            ->toList();

        if (count($matches) !== 1) {
            return null;
        }

        $user = $matches[0] instanceof User ? $matches[0] : null;
        if ($user === null) {
            return null;
        }
        if ($user->group === null) {
            return null;
        }

        $storedSub = $user->get('cognito_sub');
        if (is_string($storedSub) && $storedSub !== '') {
            return hash_equals($storedSub, $cognitoSub) ? $user : null;
        }

        $updated = $this->updateAll(
            ['cognito_sub' => $cognitoSub],
            ['id' => $user->id, 'cognito_sub IS' => null],
        );
        if ($updated !== 1) {
            $linkedUserEntity = $this->find()
                ->contain(['Groups'])
                ->where(['Users.cognito_sub' => $cognitoSub, 'Users.id' => $user->id])
                ->first();
            $linkedUser = $linkedUserEntity instanceof User ? $linkedUserEntity : null;

            return $linkedUser?->group !== null ? $linkedUser : null;
        }

        return $user;
    }

    /**
     * @param \App\Model\Entity\User $user User to inspect.
     * @return bool
     */
    private function calculateNonDistrictEmail(User $user): bool
    {
        return $this->isNonDistrictEmailForGroup($user->email, $this->groupFor($user));
    }

    /**
     * @param \App\Model\Entity\User $user User whose group is required.
     * @return \App\Model\Entity\Group|null
     */
    private function groupFor(User $user): ?Group
    {
        $group = $user->get('group');
        if (!$group instanceof Group || Value::string($group->id) !== Value::string($user->group_id)) {
            $group = $this->associatedQuery($this->Groups, $this->typedAssociation($this->Groups, GroupsTable::class))
                ->where(['Groups.id' => $user->group_id])
                ->first();
        }

        return $group;
    }

    /**
     * Check an email address against an account's registered group domains.
     *
     * @param string $email Email address to inspect.
     * @param \App\Model\Entity\Account|null $account Owning account, with its group loaded.
     * @return bool
     */
    public function isNonDistrictEmail(string $email, ?Account $account): bool
    {
        return $this->isNonDistrictEmailForGroup($email, $account?->group);
    }

    /**
     * Check an email address against a group's registered domains.
     *
     * @param string $email Email address to inspect.
     * @param \App\Model\Entity\Group|null $group Group whose domains are checked.
     * @return bool
     */
    private function isNonDistrictEmailForGroup(string $email, ?Group $group): bool
    {
        $domains = $group?->domains;
        if (!is_array($domains) || $domains === []) {
            return false;
        }

        $emailDomain = strrchr(trim($email), '@');
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
        $rules->add($rules->existsIn(['group_id'], 'Groups'), ['errorField' => 'group_id']);

        return $rules;
    }
}
