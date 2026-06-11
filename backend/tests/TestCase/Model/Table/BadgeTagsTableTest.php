<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Enum\TagCategory;
use App\Model\Table\BadgeTagsTable;
use Cake\TestSuite\TestCase;

class BadgeTagsTableTest extends TestCase
{
    protected array $fixtures = [
        'app.Badges',
        'app.BadgeTags',
        'app.BadgesBadgeTags',
    ];

    private BadgeTagsTable $BadgeTags;

    protected function setUp(): void
    {
        parent::setUp();
        $this->BadgeTags = $this->getTableLocator()->get('BadgeTags');
    }

    public function testValidationDefault(): void
    {
        $invalid = $this->BadgeTags->newEntity([
            'tag_name' => '',
            'tag_search_text' => '',
            'tag_category' => 999,
            'tag_order' => 'not-an-integer',
        ]);

        $this->assertArrayHasKey('tag_name', $invalid->getErrors());
        $this->assertArrayHasKey('tag_search_text', $invalid->getErrors());
        $this->assertArrayHasKey('tag_category', $invalid->getErrors());
        $this->assertArrayHasKey('tag_order', $invalid->getErrors());

        $valid = $this->BadgeTags->newEntity([
            'tag_name' => 'Cubs',
            'tag_search_text' => 'cub cubs',
            'tag_category' => TagCategory::Sections->value,
            'tag_order' => 30,
        ]);
        $this->assertSame([], $valid->getErrors());
    }

    public function testSaveCastsTagCategoryToEnum(): void
    {
        $tag = $this->BadgeTags->newEntity([
            'tag_name' => 'Staged Activity Badge',
            'tag_search_text' => 'staged activity badge',
            'tag_category' => TagCategory::BadgeTypes->value,
            'tag_order' => 40,
        ]);

        $this->BadgeTags->saveOrFail($tag);
        $saved = $this->BadgeTags->get($tag->id);

        $this->assertSame(TagCategory::BadgeTypes, $saved->tag_category);
        $this->assertSame(40, $saved->tag_order);
    }

    public function testTagOrderDefaultsToZero(): void
    {
        $tag = $this->BadgeTags->newEntity([
            'tag_name' => 'Default Order',
            'tag_search_text' => 'default order',
            'tag_category' => TagCategory::Sections->value,
        ]);

        $this->BadgeTags->saveOrFail($tag);

        $this->assertSame(0, $this->BadgeTags->get($tag->id)->tag_order);
    }

    public function testBadgesManyToManyAssociation(): void
    {
        $tag = $this->BadgeTags->get(
            '3bb4858e-83d2-4ddd-8f72-bab835e05a2d',
            contain: ['Badges'],
        );

        $this->assertCount(1, $tag->badges);
        $this->assertSame(
            'f525eb6d-021c-4ef2-811f-feac8db8d35d',
            $tag->badges[0]->id,
        );
    }
}
