<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Entity\BadgeSection;
use App\Model\Enum\TagCategory;
use Cake\TestSuite\TestCase;

class BadgeSectionsTableTest extends TestCase
{
    protected array $fixtures = [
        'app.BadgeTags',
    ];

    public function testFindReturnsOnlySectionTags(): void
    {
        $sections = $this->getTableLocator()->get('BadgeSections')->find()->all();

        $this->assertCount(1, $sections);
        $this->assertInstanceOf(BadgeSection::class, $sections->first());
        $this->assertSame(TagCategory::Sections, $sections->first()->tag_category);
    }

    public function testSaveForcesSectionCategory(): void
    {
        $table = $this->getTableLocator()->get('BadgeSections');
        $section = $table->newEntity([
            'tag_name' => 'Explorers',
            'tag_search_text' => 'explorer explorers',
            'tag_category' => TagCategory::BadgeTypes->value,
            'tag_order' => 30,
        ]);

        $table->saveOrFail($section);

        $this->assertSame(TagCategory::Sections, $section->tag_category);
    }
}
