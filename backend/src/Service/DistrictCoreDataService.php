<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Enum\GroupType;
use App\Model\Table\AccountsTable;
use App\Model\Table\GroupsTable;
use App\Model\Table\SectionsTable;
use App\Model\Table\TypedTableLocatorTrait;
use App\Model\Table\UsersTable;
use App\Utility\Value;
use Cake\Core\Configure;
use Cake\Http\Client;
use Cake\ORM\Locator\LocatorAwareTrait;
use RuntimeException;

class DistrictCoreDataService
{
    use TypedTableLocatorTrait;
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
        $endpoint = trim(Value::string($config['url'] ?? ''));
        $username = Value::string($config['username'] ?? '');
        $password = Value::string($config['password'] ?? '');

        if ($endpoint === '' || $username === '' || $password === '') {
            throw new RuntimeException('DistrictCoreData URL and Basic Auth credentials must be configured.');
        }

        $path = (string)(parse_url($endpoint, PHP_URL_PATH) ?? '');
        $parentUrl = preg_replace('#[^/]*$#', '', $endpoint);
        $this->baseUrl = $path === '' || $path === '/'
            ? rtrim($endpoint, '/') . '/'
            : ($parentUrl !== null && $parentUrl !== '' ? $parentUrl : rtrim($endpoint, '/') . '/');
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

        $groups = $this->typedTable('Groups', GroupsTable::class);
        $sections = $this->typedTable('Sections', SectionsTable::class);
        $accounts = $this->typedTable('Accounts', AccountsTable::class);
        $users = $this->typedTable('Users', UsersTable::class);

        $result = $groups->getConnection()->transactional(function () use (
            $groups,
            $sections,
            $accounts,
            $users,
            $groupData,
            $sectionData,
        ): array {
            $accountIds = [];
            foreach ($groupData as $record) {
                $id = Value::string($record['id']);
                $entity = $groups->find()->where(['id' => $id])->first()
                    ?? $groups->find()->where(['group_name' => $record['group_name']])->first();

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
                    'domains' => $record['domains'],
                    'type' => $record['type'],
                ]);
                $groups->saveOrFail($entity);

                $account = $accounts->find()
                    ->where(['Accounts.group_id' => $id])
                    ->orderByAsc('Accounts.account_name')
                    ->first();
                if ($account === null) {
                    $account = $accounts->newEntity([
                        'account_name' => $record['group_name'],
                        'group_id' => $id,
                    ]);
                    $accounts->saveOrFail($account);
                }
                $accountIds[$id] = $account->id;
            }

            foreach ($sectionData as $record) {
                $id = Value::string($record['id']);
                $entity = $sections->find()->where(['id' => $id])->first()
                    ?? $sections->find()->where(['section_osm_id' => $record['section_id']])->first();

                if ($entity !== null && $entity->id !== $id) {
                    $sections->updateAll(['id' => $id], ['id' => $entity->id]);
                    $entity = $sections->get($id);
                }

                if ($entity === null) {
                    $entity = $sections->newEmptyEntity();
                    $entity->set('id', $id);
                }

                $sectionFields = [
                    'group_id' => $record['group_id'],
                    'section_osm_id' => $record['section_id'],
                    'section_name' => $record['section_name'],
                    'section_type' => $record['section_type'],
                    'meeting_start_time' => $record['meeting_start_time'] ?? null,
                    'meeting_end_time' => $record['meeting_end_time'] ?? null,
                    'meeting_day' => $record['meeting_day'] ?? null,
                ];
                $hasValidAccount = $entity->account_id !== null && $accounts->exists([
                    'id' => $entity->account_id,
                    'group_id' => $record['group_id'],
                ]);
                if (!$hasValidAccount) {
                    $sectionFields['account_id'] = $accountIds[Value::string($record['group_id'])];
                }
                $sections->patchEntity($entity, $sectionFields);
                $sections->saveOrFail($entity);
            }

            foreach ($users->find()->contain(['Groups']) as $user) {
                $users->refreshNonDistrictEmail($user);
            }

            return ['groups' => count($groupData), 'sections' => count($sectionData)];
        });
        if (!is_array($result) || !is_int($result['groups'] ?? null) || !is_int($result['sections'] ?? null)) {
            throw new RuntimeException('The core data transaction did not commit.');
        }

        return ['groups' => $result['groups'], 'sections' => $result['sections']];
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

        return Value::maps($data);
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
                !isset($group['id'], $group['group_name'], $group['sort_order'], $group['domains'], $group['type'])
                || !is_string($group['id'])
                || !is_string($group['group_name'])
                || !is_int($group['sort_order'])
                || !is_array($group['domains'])
                || $group['domains'] === []
                || array_filter(
                    $group['domains'],
                    static fn(mixed $domain): bool => !is_string($domain) || $domain === '',
                ) !== []
                || !is_string($group['type'])
                || GroupType::tryFrom($group['type']) === null
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
