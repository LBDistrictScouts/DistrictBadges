<?php
declare(strict_types=1);

namespace App\Model\Behavior;

use ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\I18n\DateTime;
use Cake\ORM\Behavior;
use Cake\Routing\Router;

/** Track record creation and update times and the authenticated user responsible. */
class UserTrackingBehavior extends Behavior
{
    /**
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'createdDateField' => 'created_date',
        'updatedDateField' => 'updated_date',
        'userCreatedField' => 'user_created',
        'userUpdatedField' => 'user_updated',
    ];

    /**
     * Add associations used to display the users responsible for each record.
     *
     * @param array<string, mixed> $config Behavior configuration.
     * @return void
     */
    public function initialize(array $config): void
    {
        $this->_table->belongsTo('CreatedByUser', [
            'className' => 'Users',
            'foreignKey' => (string)$this->getConfig('userCreatedField'),
            'joinType' => 'LEFT',
            'propertyName' => 'created_by_user',
        ]);
        $this->_table->belongsTo('UpdatedByUser', [
            'className' => 'Users',
            'foreignKey' => (string)$this->getConfig('userUpdatedField'),
            'joinType' => 'LEFT',
            'propertyName' => 'updated_by_user',
        ]);
    }

    /**
     * Populate audit fields before each save.
     *
     * A `userId` save option can be supplied by trusted command-line or service
     * code. Web requests use the validated identity stored in the session.
     *
     * @param \Cake\Event\EventInterface $event Event instance.
     * @param \Cake\Datasource\EntityInterface $entity Entity being saved.
     * @param \ArrayObject<string, mixed> $options Save options.
     * @return void
     */
    public function beforeSave(
        EventInterface $event,
        EntityInterface $entity,
        ArrayObject $options,
    ): void {
        $isNew = $entity->isNew();
        $now = DateTime::now();
        $userId = $this->currentUserId($options);

        if ($isNew) {
            $this->setField('createdDateField', $entity, $now);
            $this->setField('userCreatedField', $entity, $userId);
        } else {
            $this->keepCreationFieldUnchanged('createdDateField', $entity);
            $this->keepCreationFieldUnchanged('userCreatedField', $entity);
        }

        $this->setField('updatedDateField', $entity, $now);
        $this->setField('userUpdatedField', $entity, $userId);
    }

    /**
     * Resolve the current actor, preferring an explicit trusted save option.
     *
     * @param \ArrayObject<string, mixed> $options Save options.
     * @return string|null
     */
    private function currentUserId(ArrayObject $options): ?string
    {
        if ($options->offsetExists('userId')) {
            $userId = $options['userId'];

            return is_string($userId) && $userId !== '' ? $userId : null;
        }

        $request = Router::getRequest();
        if ($request === null) {
            return null;
        }

        $identity = $request->getSession()->read('Auth.identity');
        if (
            !is_array($identity)
            || !is_string($identity['user_id'] ?? null)
            || !is_int($identity['expires_at'] ?? null)
            || $identity['expires_at'] <= time()
        ) {
            return null;
        }

        return $identity['user_id'];
    }

    /**
     * Set a configured field when it exists on the attached table.
     *
     * @param string $configKey Configuration key containing the field name.
     * @param \Cake\Datasource\EntityInterface $entity Entity being saved.
     * @param mixed $value Value to assign.
     * @return void
     */
    private function setField(string $configKey, EntityInterface $entity, mixed $value): void
    {
        $field = (string)$this->getConfig($configKey);
        if ($this->_table->getSchema()->hasColumn($field)) {
            $entity->set($field, $value);
        }
    }

    /**
     * Prevent updates from changing creation fields without requiring their
     * original values to be present on a partially loaded legacy record.
     *
     * @param string $configKey Configuration key containing the field name.
     * @param \Cake\Datasource\EntityInterface $entity Entity being saved.
     * @return void
     */
    private function keepCreationFieldUnchanged(string $configKey, EntityInterface $entity): void
    {
        $field = (string)$this->getConfig($configKey);
        if (
            $this->_table->getSchema()->hasColumn($field)
            && $entity->isDirty($field)
        ) {
            $entity->setDirty($field, false);
        }
    }
}
