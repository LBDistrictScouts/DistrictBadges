<?php
declare(strict_types=1);

namespace App\Controller\Component;

use App\Model\Enum\BadgeStatus;
use Cake\Controller\Component;
use Cake\Datasource\EntityInterface;
use Cake\Http\Response;
use Cake\ORM\Table;

class StockTransactionLinesComponent extends Component
{
    /**
     * @param \Cake\ORM\Table $linesTable Stock transaction child table.
     * @return array<string, string>
     */
    public function badgeOptions(Table $linesTable): array
    {
        return $linesTable->Badges
            ->find('list', valueField: 'badge_name')
            ->where(['status !=' => BadgeStatus::Unstocked->value])
            ->orderBy(['badge_name' => 'ASC'])
            ->toArray();
    }

    /**
     * @param array<string, mixed> $data Request data.
     * @param string $parentId Parent entity id.
     * @param array<string, mixed> $config Grid configuration.
     * @return array<string, mixed>
     */
    public function normalise(array $data, string $parentId, array $config): array
    {
        $inputKey = (string)$config['inputKey'];
        $foreignKey = (string)$config['foreignKey'];
        $lines = $data[$inputKey] ?? [];
        $data[$inputKey] = [];

        if (!is_array($lines)) {
            return $data;
        }

        foreach ($lines as $line) {
            if (!is_array($line)) {
                continue;
            }

            $data[$inputKey][] = $this->normaliseLine($line, $parentId, $foreignKey, $config);
        }

        return $data;
    }

    /**
     * @param \Cake\Datasource\EntityInterface $entity Parent entity.
     * @param array<string, mixed> $data Normalised data.
     * @param array<string, mixed> $config Grid configuration.
     * @return void
     */
    public function requireLines(EntityInterface $entity, array $data, array $config): void
    {
        $inputKey = (string)$config['inputKey'];
        if (empty($data[$inputKey])) {
            $entity->setError(
                $inputKey,
                (string)($config['requiredMessage'] ?? __('Add at least one stock transaction line.')),
            );
        }
    }

    /**
     * @param \Cake\ORM\Table $linesTable Stock transaction child table.
     * @param array<string, mixed> $config Grid configuration.
     * @return \Cake\Http\Response
     */
    public function rowResponse(Table $linesTable, array $config): Response
    {
        $controller = $this->getController();
        $request = $controller->getRequest();
        $request->allowMethod(['post']);

        $badgeId = (string)$request->getData('badge_id');
        $index = filter_var(
            $request->getData('index'),
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 0]],
        );
        $values = $this->validateInputValues($request->getData(), $config);

        if ($badgeId === '' || $index === false || $values === null) {
            return $this->errorResponse(
                (string)($config['invalidMessage'] ?? __('Complete all line fields with valid values.')),
            );
        }

        $badge = $linesTable->Badges
            ->find()
            ->select(['id', 'badge_name'])
            ->where([
                'id' => $badgeId,
                'status !=' => BadgeStatus::Unstocked->value,
            ])
            ->first();

        if ($badge === null) {
            return $this->errorResponse(__('The selected badge could not be found.'));
        }

        $selectors = $this->validateSelectors(
            $request->getData(),
            $badgeId,
            $linesTable,
            $config,
        );
        if ($selectors === null) {
            return $this->errorResponse(
                (string)($config['invalidMessage'] ?? __('Complete all line fields with valid values.')),
            );
        }

        $view = $controller->createView();
        $html = $view->StockTransactionLines->row([
            'inputKey' => $config['inputKey'],
            'badgeId' => (string)$badge->id,
            'badgeName' => (string)$badge->badge_name,
            'values' => $values,
            'fields' => $config['fields'],
            'selectors' => $selectors,
            'index' => $index,
        ]);

        return $controller->getResponse()
            ->withType('application/json')
            ->withStringBody((string)json_encode(['html' => $html]));
    }

    /**
     * @param \Cake\ORM\Table $linesTable Line table with a Badges association.
     * @param array<string, mixed> $config Grid configuration.
     * @return \Cake\Http\Response
     */
    public function badgePriceResponse(Table $linesTable, array $config): Response
    {
        $controller = $this->getController();
        $request = $controller->getRequest();
        $request->allowMethod(['get']);

        $badgeId = (string)$request->getQuery('badge_id');
        $priceField = (string)($config['badgePriceField'] ?? '');
        $badges = $linesTable->Badges;

        if (
            $badgeId === ''
            || $priceField === ''
            || !$badges->getSchema()->hasColumn($priceField)
        ) {
            return $this->errorResponse(__('A valid badge price could not be resolved.'));
        }

        $badge = $badges->find()
            ->select(['id', $priceField])
            ->where([
                'id' => $badgeId,
                'status !=' => BadgeStatus::Unstocked->value,
            ])
            ->disableHydration()
            ->first();

        if ($badge === null) {
            return $this->errorResponse(__('The selected badge could not be found.'));
        }

        return $controller->getResponse()
            ->withType('application/json')
            ->withStringBody((string)json_encode([
                'unit_price' => number_format((float)$badge[$priceField], 2, '.', ''),
            ]));
    }

    /**
     * @param array<string, mixed> $line Line input.
     * @param string $parentId Parent entity id.
     * @param string $foreignKey Parent foreign key.
     * @param array<string, mixed> $config Grid configuration.
     * @return array<string, mixed>
     */
    private function normaliseLine(
        array $line,
        string $parentId,
        string $foreignKey,
        array $config,
    ): array {
        $normalised = [
            'badge_id' => $line['badge_id'] ?? null,
            $foreignKey => $parentId,
        ];
        foreach ($config['selectors'] ?? [] as $field => $selectorConfig) {
            $normalised[$field] = $line[$field] ?? null;
        }
        if ($config['stockTransaction'] ?? true) {
            $normalised += [
                'on_hand_quantity_change' => 0,
                'receipted_quantity_change' => 0,
                'pending_quantity_change' => 0,
                'fulfilled_quantity_change' => 0,
            ];
        }
        $normalised += $config['defaults'] ?? [];

        $values = $this->validateInputValues($line, $config) ?? [];
        foreach ($config['fields'] as $field => $fieldConfig) {
            $value = $values[$field] ?? null;
            if (isset($fieldConfig['target'])) {
                $normalised[$fieldConfig['target']] = $value;
            }
            foreach ($fieldConfig['changes'] ?? [] as $column => $multiplier) {
                $normalised[$column] = $value === null ? null : (int)$value * (int)$multiplier;
            }
        }

        return $normalised;
    }

    /**
     * @param array<string, mixed> $data Request data.
     * @param string $badgeId Selected badge id.
     * @param \Cake\ORM\Table $linesTable Stock transaction child table.
     * @param array<string, mixed> $config Grid configuration.
     * @return array<string, array{value: string, label: string}>|null
     */
    private function validateSelectors(
        array $data,
        string $badgeId,
        Table $linesTable,
        array $config,
    ): ?array {
        $selectors = [];
        foreach ($config['selectors'] ?? [] as $field => $selectorConfig) {
            $value = (string)($data[$field] ?? '');
            $options = $selectorConfig['options'] ?? [];
            if ($value === '' || !is_array($options) || !isset($options[$value])) {
                return null;
            }

            $associationName = (string)($selectorConfig['association'] ?? '');
            if ($associationName !== '') {
                $target = $linesTable->getAssociation($associationName)->getTarget();
                $record = $target->find()
                    ->where([$target->getPrimaryKey() => $value])
                    ->first();
                if ($record === null) {
                    return null;
                }
                $matchField = $selectorConfig['matchBadgeField'] ?? null;
                if ($matchField !== null && (string)$record->get((string)$matchField) !== $badgeId) {
                    return null;
                }
            }

            $selectors[$field] = [
                'value' => $value,
                'label' => (string)$options[$value],
            ];
        }

        return $selectors;
    }

    /**
     * @param array<string, mixed> $data Request data.
     * @param array<string, mixed> $config Grid configuration.
     * @return array<string, int|string>|null
     */
    private function validateInputValues(array $data, array $config): ?array
    {
        $values = [];
        foreach ($config['fields'] as $field => $fieldConfig) {
            $value = isset($fieldConfig['calculation'])
                ? $this->calculateValue($values, $fieldConfig)
                : $this->validateValue($data[$field] ?? null, $fieldConfig);
            if ($value === null) {
                return null;
            }
            $values[$field] = $value;
        }

        return $values;
    }

    /**
     * @param array<string, int|string> $values Validated field values.
     * @param array<string, mixed> $config Field configuration.
     * @return string|int|null
     */
    private function calculateValue(array $values, array $config): int|string|null
    {
        $calculation = $config['calculation'];
        if (
            !is_array($calculation)
            || ($calculation['operation'] ?? null) !== 'multiply'
            || !isset($calculation['fields'])
            || !is_array($calculation['fields'])
        ) {
            return null;
        }

        $result = 1.0;
        foreach ($calculation['fields'] as $field) {
            if (!array_key_exists($field, $values) || !is_numeric($values[$field])) {
                return null;
            }
            $result *= (float)$values[$field];
        }

        return $this->validateValue($result, $config);
    }

    /**
     * @param mixed $value Input value.
     * @param array<string, mixed> $config Field configuration.
     * @return string|int|null
     */
    private function validateValue(mixed $value, array $config): int|string|null
    {
        if (($config['type'] ?? 'integer') === 'decimal') {
            if (!is_numeric($value)) {
                return null;
            }

            $validated = (float)$value;
            if (
                $validated < (float)($config['min'] ?? 0)
                || $validated > (float)($config['max'] ?? PHP_INT_MAX)
            ) {
                return null;
            }

            return number_format($validated, 2, '.', '');
        }

        $validated = filter_var($value, FILTER_VALIDATE_INT, [
            'options' => [
                'min_range' => $config['min'] ?? 0,
                'max_range' => $config['max'] ?? PHP_INT_MAX,
            ],
        ]);

        return $validated === false ? null : $validated;
    }

    /**
     * @param string $message Error message.
     * @return \Cake\Http\Response
     */
    private function errorResponse(string $message): Response
    {
        return $this->getController()->getResponse()
            ->withStatus(422)
            ->withType('application/json')
            ->withStringBody((string)json_encode(['error' => $message]));
    }
}
