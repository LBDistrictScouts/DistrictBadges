<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

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
        $this->assertSame($sectionId, $this->getTableLocator()->get('Sections')->find()->firstOrFail()->id);
        $this->assertSame($groupId, $this->getTableLocator()->get('Accounts')->find()->firstOrFail()->group_id);
    }
}
