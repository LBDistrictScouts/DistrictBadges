<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Core\Configure;
use Cake\Http\Client;
use Cake\ORM\Locator\LocatorAwareTrait;
use RuntimeException;

class DistrictCoreDataService
{
    use LocatorAwareTrait;

    private Client $client;
    private string $baseUrl;
    private string $authorization;

    /**
     * @param array<string, mixed>|null $config Service configuration.
     * @param \Cake\Http\Client|null $client HTTP client override.
     */
    public function __construct(?array $config = null, ?Client $client = null)
    {
        $config ??= (array)Configure::read('DistrictCoreData');
        $endpoint = trim((string)($config['url'] ?? ''));
        $username = (string)($config['username'] ?? '');
        $password = (string)($config['password'] ?? '');

        if ($endpoint === '' || $username === '' || $password === '') {
            throw new RuntimeException('DistrictCoreData URL and Basic Auth credentials must be configured.');
        }

        $path = (string)(parse_url($endpoint, PHP_URL_PATH) ?? '');
        $this->baseUrl = $path === '' || $path === '/'
            ? rtrim($endpoint, '/') . '/'
            : (preg_replace('#[^/]*$#', '', $endpoint) ?: rtrim($endpoint, '/') . '/');
        $this->authorization = 'Basic ' . base64_encode($username . ':' . $password);
        $this->client = $client ?? new Client();
    }

    /**
     * @return array{groups: list<array<string, mixed>>, sections: list<array<string, mixed>>}
     */
    public function fetch(): array
    {
        return [
            'groups' => $this->fetchDataset('groups.json'),
            'sections' => $this->fetchDataset('sections.json'),
        ];
    }

    /**
     * @param list<array<string, mixed>> $groupData Group dataset.
     * @param list<array<string, mixed>> $sectionData Section dataset.
     * @return array{groups: int, sections: int}
     */
    public function sync(array $groupData, array $sectionData): array
    {
        $this->validateDatasets($groupData, $sectionData);

        /** @var \App\Model\Table\GroupsTable $groups */
        $groups = $this->getTableLocator()->get('Groups');
        /** @var \App\Model\Table\SectionsTable $sections */
        $sections = $this->getTableLocator()->get('Sections');

        return $groups->getConnection()->transactional(function () use (
            $groups,
            $sections,
            $groupData,
            $sectionData,
        ): array {
            foreach ($groupData as $record) {
                $id = (string)$record['id'];
                $entity = $groups->find()->where(['group_name' => $record['group_name']])->first();

                if ($entity !== null && $entity->id !== $id) {
                    $groups->updateAll(['id' => $id], ['id' => $entity->id]);
                    $entity = $groups->get($id);
                }

                if ($entity === null) {
                    $entity = $groups->newEmptyEntity();
                    $entity->set('id', $id);
                }

                $groups->patchEntity($entity, [
                    'group_name' => $record['group_name'],
                    'sort_order' => $record['sort_order'],
                ]);
                $groups->saveOrFail($entity);
            }

            foreach ($sectionData as $record) {
                $id = (string)$record['id'];
                $entity = $sections->find()->where(['section_osm_id' => $record['section_id']])->first();

                if ($entity !== null && $entity->id !== $id) {
                    $sections->updateAll(['id' => $id], ['id' => $entity->id]);
                    $entity = $sections->get($id);
                }

                if ($entity === null) {
                    $entity = $sections->newEmptyEntity();
                    $entity->set('id', $id);
                }

                $sections->patchEntity($entity, [
                    'group_id' => $record['group_id'],
                    'section_osm_id' => $record['section_id'],
                    'section_name' => $record['section_name'],
                    'section_type' => $record['section_type'],
                    'meeting_start_time' => $record['meeting_start_time'] ?? null,
                    'meeting_end_time' => $record['meeting_end_time'] ?? null,
                    'meeting_day' => $record['meeting_day'] ?? null,
                ]);
                $sections->saveOrFail($entity);
            }

            return ['groups' => count($groupData), 'sections' => count($sectionData)];
        });
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchDataset(string $filename): array
    {
        $response = $this->client->get($this->baseUrl . $filename, [], [
            'headers' => [
                'Authorization' => $this->authorization,
                'Accept' => 'application/json',
            ],
        ]);

        if (!$response->isOk()) {
            throw new RuntimeException(sprintf(
                'DistrictCoreData %s request failed with status %d.',
                $filename,
                $response->getStatusCode(),
            ));
        }

        $data = $response->getJson();
        if (!is_array($data) || !array_is_list($data)) {
            throw new RuntimeException(sprintf('DistrictCoreData %s did not return a JSON list.', $filename));
        }

        return $data;
    }

    /**
     * @param list<array<string, mixed>> $groups Group dataset.
     * @param list<array<string, mixed>> $sections Section dataset.
     */
    private function validateDatasets(array $groups, array $sections): void
    {
        if ($groups === [] || $sections === []) {
            throw new RuntimeException('DistrictCoreData groups and sections must not be empty.');
        }

        $groupIds = [];
        foreach ($groups as $group) {
            if (
                !isset($group['id'], $group['group_name'], $group['sort_order'])
                || !is_string($group['id'])
                || !is_string($group['group_name'])
                || !is_int($group['sort_order'])
            ) {
                throw new RuntimeException('DistrictCoreData group record is invalid.');
            }
            $groupIds[$group['id']] = true;
        }

        foreach ($sections as $section) {
            if (
                !isset(
                    $section['id'],
                    $section['group_id'],
                    $section['section_id'],
                    $section['section_name'],
                    $section['section_type'],
                )
                || !is_string($section['id'])
                || !is_string($section['group_id'])
                || !is_int($section['section_id'])
                || !is_string($section['section_name'])
                || !is_string($section['section_type'])
                || !isset($groupIds[$section['group_id']])
            ) {
                throw new RuntimeException('DistrictCoreData section record is invalid.');
            }
        }
    }
}
