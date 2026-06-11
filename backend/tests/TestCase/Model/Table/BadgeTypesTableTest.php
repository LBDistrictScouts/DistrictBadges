<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Entity\BadgeType;
use App\Model\Enum\TagCategory;
use Cake\TestSuite\TestCase;

class BadgeTypesTableTest extends TestCase
{
    protected array $fixtures = [
        'app.BadgeTags',
    ];

    public function testFindReturnsOnlyBadgeTypeTags(): void
    {
        $types = $this->getTableLocator()->get('BadgeTypes')->find()->all();

        $this->assertCount(1, $types);
        $this->assertInstanceOf(BadgeType::class, $types->first());
        $this->assertSame(TagCategory::BadgeTypes, $types->first()->tag_category);
    }

    public function testSaveForcesBadgeTypeCategory(): void
    {
        $table = $this->getTableLocator()->get('BadgeTypes');
        $type = $table->newEntity([
            'tag_name' => 'Challenge',
            'tag_search_text' => 'challenge',
            'tag_category' => TagCategory::Sections->value,
            'tag_order' => 30,
        ]);

        $table->saveOrFail($type);

        $this->assertSame(TagCategory::BadgeTypes, $type->tag_category);
    }
}
