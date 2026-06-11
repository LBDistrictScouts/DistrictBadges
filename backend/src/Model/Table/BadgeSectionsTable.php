<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Enum\TagCategory;
use ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Query\SelectQuery;

class BadgeSectionsTable extends BadgeTagsTable
{
    /**
     * @param array<string, mixed> $config Table configuration.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setEntityClass('App\Model\Entity\BadgeSection');
    }

    /**
     * @param \Cake\Event\EventInterface $event Event.
     * @param \Cake\ORM\Query\SelectQuery $query Query.
     * @param \ArrayObject $options Options.
     * @param bool $primary Primary query.
     * @return void
     */
    public function beforeFind(
        EventInterface $event,
        SelectQuery $query,
        ArrayObject $options,
        bool $primary,
    ): void {
        $query->where([$this->aliasField('tag_category') => TagCategory::Sections->value]);
    }

    /**
     * @param \Cake\Event\EventInterface $event Event.
     * @param \ArrayObject $data Marshalled data.
     * @param \ArrayObject $options Options.
     * @return void
     */
    public function beforeMarshal(
        EventInterface $event,
        ArrayObject $data,
        ArrayObject $options,
    ): void {
        $data['tag_category'] = TagCategory::Sections->value;
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
        $entity->set('tag_category', TagCategory::Sections);
    }
}
