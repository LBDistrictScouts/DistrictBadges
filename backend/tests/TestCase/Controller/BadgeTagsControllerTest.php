<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Model\Enum\TagCategory;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\BadgeTagsController Test Case
 *
 * @link \App\Controller\BadgeTagsController
 */
class BadgeTagsControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.BadgeTags',
        'app.Badges',
        'app.BadgesBadgeTags',
    ];

    /**
     * Test index method
     *
     * @return void
     * @link \App\Controller\BadgeTagsController::index()
     */
    public function testIndex(): void
    {
        $this->get('/badge-tags');

        $this->assertResponseOk();
        $this->assertResponseContains('Beavers');
        $this->assertResponseContains('Activity Badge');
        $this->assertResponseContains(TagCategory::Sections->label());
        $this->assertResponseContains(TagCategory::BadgeTypes->label());
        $this->assertResponseContains('Tag Order');
        $this->assertResponseContains('<h3>Badge Tags</h3>');
        $this->assertResponseNotContains('Show All Tags');
        $this->assertResponseContains('href="/badge-tags?category=' . TagCategory::Sections->value . '"');
        $this->assertResponseContains('href="/badge-tags?category=' . TagCategory::BadgeTypes->value . '"');
    }

    public function testIndexWithCategoryShowsCategoryTitleAndShowAllLink(): void
    {
        $this->get('/badge-tags?category=' . TagCategory::BadgeTypes->value);

        $this->assertResponseOk();
        $this->assertResponseContains('<h3>Badge Type Tags</h3>');
        $this->assertResponseContains('Show All Tags');
        $this->assertResponseContains('button button-outline float-right');
        $this->assertResponseNotContains('Beavers');

        $this->get('/badge-tags?category=' . TagCategory::Sections->value);

        $this->assertResponseOk();
        $this->assertResponseContains('<h3>Section Tags</h3>');
    }

    public function testIndexSortingOverridesDefaultOrder(): void
    {
        $badgeTags = $this->getTableLocator()->get('BadgeTags');
        $badgeTags->saveOrFail($badgeTags->newEntity([
            'tag_name' => 'AAA sortable tag',
            'tag_search_text' => 'aaa sortable tag',
            'tag_category' => TagCategory::Sections->value,
            'tag_order' => 999,
        ]));

        $this->get('/badge-tags');
        $defaultBody = (string)$this->_response->getBody();
        $this->assertLessThan(
            strpos($defaultBody, 'AAA sortable tag'),
            strpos($defaultBody, 'Beavers'),
        );

        $this->get('/badge-tags?sort=tag_name&direction=asc');
        $sortedBody = (string)$this->_response->getBody();
        $this->assertLessThan(
            strpos($sortedBody, 'Beavers'),
            strpos($sortedBody, 'AAA sortable tag'),
        );
    }

    /**
     * Test view method
     *
     * @return void
     * @link \App\Controller\BadgeTagsController::view()
     */
    public function testView(): void
    {
        $id = '3bb4858e-83d2-4ddd-8f72-bab835e05a2d';

        $this->get("/badge-tags/view/{$id}");

        $this->assertResponseOk();
        $this->assertResponseContains('Beavers');
        $this->assertResponseContains('Beavers');
        $this->assertResponseContains(TagCategory::Sections->label());
        $this->assertResponseContains('Related Badges');
        $this->assertResponseContains('Lorem ipsum dolor sit amet');
    }

    /**
     * Test add method
     *
     * @return void
     * @link \App\Controller\BadgeTagsController::add()
     */
    public function testAdd(): void
    {
        $badgeTags = $this->getTableLocator()->get('BadgeTags');
        $before = $badgeTags->find()->count();

        $this->enableCsrfToken();
        $this->post('/badge-tags/add', [
            'tag_name' => 'Challenge',
            'tag_search_text' => 'challenge badge',
            'tag_category' => TagCategory::BadgeTypes->value,
            'tag_order' => 30,
        ]);

        $this->assertRedirect(['controller' => 'BadgeTags', 'action' => 'index']);
        $this->assertFlashMessage('The badge tag has been saved.');
        $this->assertSame($before + 1, $badgeTags->find()->count());

        $saved = $badgeTags->find()
            ->where(['tag_name' => 'Challenge'])
            ->firstOrFail();
        $this->assertSame('challenge badge', $saved->tag_search_text);
        $this->assertSame(TagCategory::BadgeTypes, $saved->tag_category);
        $this->assertSame(30, $saved->tag_order);
    }

    /**
     * Test edit method
     *
     * @return void
     * @link \App\Controller\BadgeTagsController::edit()
     */
    public function testEdit(): void
    {
        $badgeTags = $this->getTableLocator()->get('BadgeTags');
        $id = '3bb4858e-83d2-4ddd-8f72-bab835e05a2d';

        $this->enableCsrfToken();
        $this->put("/badge-tags/edit/{$id}", [
            'tag_name' => 'Squirrels',
            'tag_search_text' => 'squirrel squirrels',
            'tag_category' => TagCategory::Sections->value,
            'tag_order' => 5,
        ]);

        $this->assertRedirect(['controller' => 'BadgeTags', 'action' => 'index']);
        $this->assertFlashMessage('The badge tag has been saved.');

        $updated = $badgeTags->get($id);
        $this->assertSame('Squirrels', $updated->tag_name);
        $this->assertSame('squirrel squirrels', $updated->tag_search_text);
        $this->assertSame(TagCategory::Sections, $updated->tag_category);
        $this->assertSame(5, $updated->tag_order);
    }

    /**
     * Test delete method
     *
     * @return void
     * @link \App\Controller\BadgeTagsController::delete()
     */
    public function testDelete(): void
    {
        $badgeTags = $this->getTableLocator()->get('BadgeTags');
        $id = '1cc59bf0-37b3-42db-a5af-47745ac381d5';

        $this->enableCsrfToken();
        $this->delete("/badge-tags/delete/{$id}");

        $this->assertRedirect(['controller' => 'BadgeTags', 'action' => 'index']);
        $this->assertFlashMessage('The badge tag has been deleted.');
        $this->assertFalse($badgeTags->exists(['id' => $id]));
    }

    public function testFormsDoNotExposeBadgeAssociationControl(): void
    {
        $this->get('/badge-tags/add');
        $this->assertResponseOk();
        $this->assertResponseNotContains('badges[_ids]');

        $id = '3bb4858e-83d2-4ddd-8f72-bab835e05a2d';
        $this->get("/badge-tags/edit/{$id}");
        $this->assertResponseOk();
        $this->assertResponseNotContains('badges[_ids]');
    }
}
