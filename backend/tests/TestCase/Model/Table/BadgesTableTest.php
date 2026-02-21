<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\BadgesTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\BadgesTable Test Case
 */
class BadgesTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\BadgesTable
     */
    protected $Badges;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Badges',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('Badges') ? [] : ['className' => BadgesTable::class];
        $this->Badges = $this->getTableLocator()->get('Badges', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->Badges);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @link \App\Model\Table\BadgesTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
