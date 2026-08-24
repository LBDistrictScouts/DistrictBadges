<?php
declare(strict_types=1);

namespace App\Model\Behavior;

use ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\I18n\DateTime;
use Cake\ORM\Behavior;
use DateTimeInterface;
use RuntimeException;

class EntityNumberBehavior extends Behavior
{
    /**
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'field' => null,
        'prefix' => null,
    ];

    private ?DateTime $date = null;

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
        $field = (string)$this->getConfig('field');
        if (!$entity->isNew() || $entity->hasValue($field)) {
            return;
        }

        $prefix = strtoupper((string)$this->getConfig('prefix'));
        if ($field === '' || $prefix === '') {
            throw new RuntimeException('EntityNumber behavior requires field and prefix configuration.');
        }

        $date = $this->date ?? DateTime::now();
        $period = $date->format('Y-m');
        $sequenceKey = implode(':', [$this->_table->getTable(), $prefix, $period]);
        $statement = $this->_table->getConnection()->execute(
            'INSERT INTO entity_number_sequences (sequence_key, last_number) '
            . 'VALUES (:sequence_key, 1) '
            . 'ON CONFLICT (sequence_key) DO UPDATE '
            . 'SET last_number = entity_number_sequences.last_number + 1 '
            . 'RETURNING last_number',
            ['sequence_key' => $sequenceKey],
        );
        $result = $statement->fetch('assoc');
        $increment = (int)($result['last_number'] ?? 0);
        if ($increment < 1) {
            throw new RuntimeException('Unable to generate entity number.');
        }

        $entity->set($field, sprintf('%s-%s-%d', $prefix, $period, $increment));
    }

    /**
     * Set a fixed generation date, primarily for deterministic batch operations and tests.
     *
     * @param \DateTimeInterface|null $date Date, or null to use the current time.
     * @return $this
     */
    public function setDate(?DateTimeInterface $date)
    {
        $this->date = $date === null ? null : new DateTime($date);

        return $this;
    }
}
