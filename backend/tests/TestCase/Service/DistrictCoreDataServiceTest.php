<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Model\Enum\GroupType;
use App\Service\DistrictCoreDataService;
use Cake\TestSuite\TestCase;
use ReflectionProperty;

class DistrictCoreDataServiceTest extends TestCase
{
    /**
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Groups',
        'app.Sections',
        'app.Accounts',
        'app.Users',
    ];

    /**
     * @return void
     */
    public function testBareHostEndpointResolvesDatasetsBelowHost(): void
    {
        $service = new DistrictCoreDataService([
            'url' => 'https://example.org',
            'username' => 'test',
            'password' => 'test',
        ]);

        $property = new ReflectionProperty($service, 'baseUrl');
        $this->assertSame('https://example.org/', $property->getValue($service));
    }

    /**
     * @return void
     */
    public function testSyncUsesSharedIdsAndPreservesRelationships(): void
    {
        $groupId = 'addc8a62-bc66-50cc-b899-a780e1710293';
        $sectionId = '158254fa-d205-51ed-8314-96de20a3fa95';
        $service = new DistrictCoreDataService([
            'url' => 'https://example.org/index.html',
            'username' => 'test',
            'password' => 'test',
        ]);

        $counts = $service->sync([
            [
                'id' => $groupId,
                'group_name' => 'Lorem ipsum dolor sit amet',
                'sort_order' => 1,
                'domains' => ['example.org'],
                'type' => 'group',
            ],
        ], [
            [
                'id' => $sectionId,
                'group_id' => $groupId,
                'group' => 'Lorem ipsum dolor sit amet',
                'section_id' => 14450,
                'section_name' => 'Example Beavers',
                'section_type' => 'beavers',
            ],
        ]);

        $this->assertSame(['groups' => 1, 'sections' => 1], $counts);
        $this->assertSame($groupId, $this->getTableLocator()->get('Groups')->find()->firstOrFail()->id);
        $group = $this->getTableLocator()->get('Groups')->get($groupId);
        $this->assertSame(['example.org'], $group->domains);
        $this->assertSame(GroupType::Group, $group->type);
        $section = $this->getTableLocator()->get('Sections')->find()->firstOrFail();
        $account = $this->getTableLocator()->get('Accounts')->find()->firstOrFail();
        $this->assertSame($sectionId, $section->id);
        $this->assertSame($groupId, $account->group_id);
        $this->assertSame($account->id, $section->account_id);
    }

    public function testSyncUpdatesGroupRenamedByCoreData(): void
    {
        $groupId = '4d5149f3-6214-4457-a04d-e428dc1200d7';
        $service = new DistrictCoreDataService([
            'url' => 'https://example.org/index.html',
            'username' => 'test',
            'password' => 'test',
        ]);

        $service->sync([[
            'id' => $groupId,
            'group_name' => 'Renamed Scout Group',
            'sort_order' => 1,
            'domains' => ['renamed.example.org'],
            'type' => 'district',
        ]], [[
            'id' => 'd9534dcb-a846-5a22-a2fe-b67580555563',
            'group_id' => $groupId,
            'group' => 'Renamed Scout Group',
            'section_id' => 14450,
            'section_name' => 'Example Beavers',
            'section_type' => 'beavers',
        ]]);

        $groups = $this->getTableLocator()->get('Groups');
        $this->assertSame('Renamed Scout Group', $groups->get($groupId)->group_name);
        $this->assertSame(['renamed.example.org'], $groups->get($groupId)->domains);
        $this->assertSame(GroupType::District, $groups->get($groupId)->type);
        $this->assertSame(1, $groups->find()->where(['id' => $groupId])->count());
    }

    public function testSyncRefreshesUsersNonDistrictEmailFlag(): void
    {
        $this->getTableLocator()->get('Users')->updateAll(
            ['email' => 'member@example.org'],
            ['id' => '30350fc5-a8b7-4b3e-85ae-9f2f5f3a30e1'],
        );
        $service = new DistrictCoreDataService([
            'url' => 'https://example.org/index.html',
            'username' => 'test',
            'password' => 'test',
        ]);

        $service->sync([[
            'id' => '4d5149f3-6214-4457-a04d-e428dc1200d7',
            'group_name' => 'Lorem ipsum dolor sit amet',
            'sort_order' => 1,
            'domains' => ['renamed.example.org'],
            'type' => 'group',
        ]], [[
            'id' => 'd9534dcb-a846-5a22-a2fe-b67580555563',
            'group_id' => '4d5149f3-6214-4457-a04d-e428dc1200d7',
            'section_id' => 14450,
            'section_name' => 'Example Beavers',
            'section_type' => 'beavers',
        ]]);

        $user = $this->getTableLocator()->get('Users')->get('30350fc5-a8b7-4b3e-85ae-9f2f5f3a30e1');
        $this->assertTrue($user->non_district_email);

        $service->sync([[
            'id' => '4d5149f3-6214-4457-a04d-e428dc1200d7',
            'group_name' => 'Lorem ipsum dolor sit amet',
            'sort_order' => 1,
            'domains' => ['example.org'],
            'type' => 'group',
        ]], [[
            'id' => 'd9534dcb-a846-5a22-a2fe-b67580555563',
            'group_id' => '4d5149f3-6214-4457-a04d-e428dc1200d7',
            'section_id' => 14450,
            'section_name' => 'Example Beavers',
            'section_type' => 'beavers',
        ]]);

        $user = $this->getTableLocator()->get('Users')->get('30350fc5-a8b7-4b3e-85ae-9f2f5f3a30e1');
        $this->assertFalse($user->non_district_email);
    }

    public function testSyncUpdatesSectionWhenLegacyIdChanges(): void
    {
        $groupId = '4d5149f3-6214-4457-a04d-e428dc1200d7';
        $sectionId = 'd9534dcb-a846-5a22-a2fe-b67580555563';
        $service = new DistrictCoreDataService([
            'url' => 'https://example.org/index.html',
            'username' => 'test',
            'password' => 'test',
        ]);

        $service->sync([[
            'id' => $groupId,
            'group_name' => 'Lorem ipsum dolor sit amet',
            'sort_order' => 1,
            'domains' => ['example.org'],
            'type' => 'group',
        ]], [[
            'id' => $sectionId,
            'group_id' => $groupId,
            'group' => 'Lorem ipsum dolor sit amet',
            'section_id' => 99999,
            'section_name' => 'Renamed Section',
            'section_type' => 'scouts',
        ]]);

        $sections = $this->getTableLocator()->get('Sections');
        $this->assertSame(99999, $sections->get($sectionId)->section_osm_id);
        $this->assertSame('Renamed Section', $sections->get($sectionId)->section_name);
        $this->assertSame(1, $sections->find()->where(['id' => $sectionId])->count());
    }

    public function testSyncCreatesOneDefaultAccountPerGroupAndReusesIt(): void
    {
        $groupId = 'addc8a62-bc66-50cc-b899-a780e1710293';
        $service = new DistrictCoreDataService([
            'url' => 'https://example.org/index.html',
            'username' => 'test',
            'password' => 'test',
        ]);
        $groups = [[
            'id' => $groupId,
            'group_name' => 'New Scout Group',
            'sort_order' => 2,
            'domains' => ['new.example.org'],
            'type' => 'group',
        ]];
        $sections = [[
            'id' => '158254fa-d205-51ed-8314-96de20a3fa95',
            'group_id' => $groupId,
            'section_id' => 54321,
            'section_name' => 'New Cubs',
            'section_type' => 'cubs',
        ]];

        $service->sync($groups, $sections);
        $service->sync($groups, $sections);

        $accounts = $this->getTableLocator()->get('Accounts');
        $account = $accounts->find()->where(['group_id' => $groupId])->firstOrFail();
        $section = $this->getTableLocator()->get('Sections')->get($sections[0]['id']);
        $this->assertSame(1, $accounts->find()->where(['group_id' => $groupId])->count());
        $this->assertSame('New Scout Group', $account->account_name);
        $this->assertSame($account->id, $section->account_id);
    }

    public function testSyncPreservesExistingSectionAccountAssignment(): void
    {
        $groupId = '4d5149f3-6214-4457-a04d-e428dc1200d7';
        $sectionId = 'd9534dcb-a846-5a22-a2fe-b67580555563';
        $accounts = $this->getTableLocator()->get('Accounts');
        $assignedAccount = $accounts->newEntity([
            'account_name' => 'Section Account',
            'group_id' => $groupId,
        ]);
        $accounts->saveOrFail($assignedAccount);
        $this->getTableLocator()->get('Sections')->updateAll(
            ['account_id' => $assignedAccount->id],
            ['id' => $sectionId],
        );
        $service = new DistrictCoreDataService([
            'url' => 'https://example.org/index.html',
            'username' => 'test',
            'password' => 'test',
        ]);

        $service->sync([[
            'id' => $groupId,
            'group_name' => 'Lorem ipsum dolor sit amet',
            'sort_order' => 1,
            'domains' => ['example.org'],
            'type' => 'group',
        ]], [[
            'id' => $sectionId,
            'group_id' => $groupId,
            'section_id' => 14450,
            'section_name' => 'Example Beavers',
            'section_type' => 'beavers',
        ]]);

        $section = $this->getTableLocator()->get('Sections')->get($sectionId);
        $this->assertSame($assignedAccount->id, $section->account_id);
    }
}
