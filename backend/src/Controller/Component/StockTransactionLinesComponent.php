<?php
declare(strict_types=1);

namespace App\Controller\Component;

use App\Model\Enum\BadgeStatus;
use App\Model\Table\BadgesTable;
use App\View\Helper\StockTransactionLinesHelper;
use Cake\Controller\Component;
use Cake\Datasource\EntityInterface;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Http\Response;
use Cake\ORM\Table;
use LogicException;

/**
 * @phpstan-type FieldConfig array{
 *   label?: string, source?: string, type?: string, min?: int|float, max?: int|float,
 *   step?: int|float|string, default?: int|float|string, editable?: bool, currency?: string,
 *   target?: string, changes?: array<string, int>,
 *   calculation?: array{operation: string, fields: list<string>}
 * }
 * @phpstan-type SelectorConfig array{
 *   label?: string, options?: array<string, string>, empty?: string,
 *   association?: string, matchBadgeField?: string
 * }
 * @phpstan-type DeduplicationConfig array{
 *   sum?: array<string, 'money'|'quantity'>,
 *   weightedPrice?: array{field: string, quantity: string, amount: string}
 * }
 * @phpstan-type LineConfig array{
 *   inputKey: string, foreignKey?: string, fields: array<string, FieldConfig>,
 *   selectors?: array<string, SelectorConfig>, stockTransaction?: bool,
 *   defaults?: array<string, scalar|null>, deduplicate?: DeduplicationConfig,
 *   requiredMessage?: string, invalidMessage?: string, badgePriceField?: string
 * }
 */
class StockTransactionLinesComponent extends Component
{
    /**
     * @param \Cake\ORM\Table $linesTable Stock transaction child table.
     * @return array<string, string>
     */
    public function badgeOptions(Table $linesTable): array
    {
        $badges = $this->badgesTable($linesTable)->find()
            ->select(['id', 'badge_name'])
            ->where(['status !=' => BadgeStatus::Unstocked->value])
            ->orderBy(['badge_name' => 'ASC']);
        $options = [];
        foreach ($badges as $badge) {
            $options[$badge->id] = $badge->badge_name;
        }

        return $options;
    }

    /**
     * @param array<string, mixed> $data Request data.
     * @param string $parentId Parent entity id.
     * @param LineConfig $config Grid configuration.
     * @return array<string, mixed>
     */
    public function normalise(array $data, string $parentId, array $config): array
    {
        $inputKey = $config['inputKey'];
        $foreignKey = $config['foreignKey'] ?? null;
        if ($foreignKey === null) {
            throw new LogicException('Normalising stock lines requires a foreignKey configuration.');
        }
        $lines = $data[$inputKey] ?? [];
        $data[$inputKey] = [];

        if (!is_array($lines)) {
            return $data;
        }

        $normalisedLines = [];
        foreach ($lines as $line) {
            if (!is_array($line)) {
                continue;
            }

            $normalisedLines[] = $this->normaliseLine($line, $parentId, $foreignKey, $config);
        }

        $data[$inputKey] = $this->deduplicateLines($normalisedLines, $config);

        return $data;
    }

    /**
     * @param \Cake\Datasource\EntityInterface $entity Parent entity.
     * @param array<string, mixed> $data Normalised data.
     * @param array{inputKey: string, requiredMessage?: string} $config Grid configuration.
     * @return void
     */
    public function requireLines(EntityInterface $entity, array $data, array $config): void
    {
        $inputKey = $config['inputKey'];
        if (!is_array($data[$inputKey] ?? null) || $data[$inputKey] === []) {
            $entity->setError(
                $inputKey,
                $config['requiredMessage'] ?? __('Add at least one stock transaction line.'),
            );
        }
    }

    /**
     * @param \Cake\ORM\Table $linesTable Stock transaction child table.
     * @param LineConfig $config Grid configuration.
     * @return \Cake\Http\Response
     */
    public function rowResponse(Table $linesTable, array $config): Response
    {
        $controller = $this->getController();
        $request = $controller->getRequest();
        $request->allowMethod(['post']);

        $badgeId = $this->inputIdentifier($request->getData('badge_id')) ?? '';
        $index = filter_var(
            $request->getData('index'),
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 0]],
        );
        $values = $this->validateInputValues($request->getData(), $config);

        if ($badgeId === '' || $index === false || $values === null) {
            return $this->errorResponse(
                $config['invalidMessage'] ?? __('Complete all line fields with valid values.'),
            );
        }

        $badge = $this->badgesTable($linesTable)
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
                $config['invalidMessage'] ?? __('Complete all line fields with valid values.'),
            );
        }

        $view = $controller->createView();
        $helper = $view->loadHelper('StockTransactionLines');
        if (!$helper instanceof StockTransactionLinesHelper) {
            throw new LogicException('StockTransactionLines must use StockTransactionLinesHelper.');
        }
        $fields = [];
        foreach ($config['fields'] as $field => $fieldConfig) {
            $fields[$field] = ['label' => $fieldConfig['label'] ?? $field] + $fieldConfig;
        }
        $html = $helper->row([
            'inputKey' => $config['inputKey'],
            'badgeId' => $badge->id,
            'badgeName' => $badge->badge_name,
            'values' => $values,
            'fields' => $fields,
            'selectors' => $selectors,
            'index' => $index,
        ]);

        return $controller->getResponse()
            ->withType('application/json')
            ->withStringBody(json_encode(['html' => $html], JSON_THROW_ON_ERROR));
    }

    /**
     * @param \Cake\ORM\Table $linesTable Line table with a Badges association.
     * @param array{badgePriceField?: string} $config Grid configuration.
     * @return \Cake\Http\Response
     */
    public function badgePriceResponse(Table $linesTable, array $config): Response
    {
        $controller = $this->getController();
        $request = $controller->getRequest();
        $request->allowMethod(['get']);

        $badgeId = $this->inputIdentifier($request->getQuery('badge_id')) ?? '';
        $priceField = $config['badgePriceField'] ?? '';
        $badges = $this->badgesTable($linesTable);

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
            ->first();

        if ($badge === null) {
            return $this->errorResponse(__('The selected badge could not be found.'));
        }

        $price = $badge->get($priceField);
        if ($price !== null && !is_numeric($price)) {
            return $this->errorResponse(__('A valid badge price could not be resolved.'));
        }

        return $controller->getResponse()
            ->withType('application/json')
            ->withStringBody(json_encode([
                'unit_price' => number_format((float)$price, 2, '.', ''),
            ], JSON_THROW_ON_ERROR));
    }

    /**
     * @param array<array-key, mixed> $line Line input.
     * @param string $parentId Parent entity id.
     * @param string $foreignKey Parent foreign key.
     * @param LineConfig $config Grid configuration.
     * @return array<string, scalar|null>
     */
    private function normaliseLine(
        array $line,
        string $parentId,
        string $foreignKey,
        array $config,
    ): array {
        $normalised = [
            'badge_id' => $this->inputIdentifier($line['badge_id'] ?? null),
            $foreignKey => $parentId,
        ];
        foreach ($config['selectors'] ?? [] as $field => $selectorConfig) {
            $normalised[$field] = $this->inputIdentifier($line[$field] ?? null);
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
                $normalised[$column] = $value === null ? null : (int)$value * $multiplier;
            }
        }

        return $normalised;
    }

    /**
     * Combine repeated badge rows while keeping selector-specific lines separate.
     *
     * @param list<array<string, scalar|null>> $lines Normalised lines.
     * @param LineConfig $config Grid configuration.
     * @return list<array<string, scalar|null>>
     */
    private function deduplicateLines(array $lines, array $config): array
    {
        $deduplication = $config['deduplicate'] ?? null;
        if ($deduplication === null) {
            return $lines;
        }

        $keyFields = array_values(array_unique([
            'badge_id',
            ...array_keys($config['selectors'] ?? []),
        ]));
        $grouped = [];
        $unmergeable = [];
        foreach ($lines as $line) {
            $key = [];
            foreach ($keyFields as $field) {
                $value = $line[$field] ?? null;
                if (!is_scalar($value) || (string)$value === '') {
                    $key = [];
                    break;
                }
                $key[$field] = (string)$value;
            }
            if ($key === [] || !$this->canAggregateLine($line, $deduplication)) {
                $unmergeable[] = $line;
                continue;
            }

            $groupKey = json_encode($key, JSON_THROW_ON_ERROR);
            if (!isset($grouped[$groupKey])) {
                $grouped[$groupKey] = $line;
                continue;
            }

            foreach ($deduplication['sum'] ?? [] as $field => $type) {
                if ($type === 'money') {
                    $sumCents = (int)round((float)$grouped[$groupKey][$field] * 100)
                        + (int)round((float)$line[$field] * 100);
                    $grouped[$groupKey][$field] = number_format($sumCents / 100, 2, '.', '');
                } else {
                    $grouped[$groupKey][$field] = (int)$grouped[$groupKey][$field] + (int)$line[$field];
                }
            }
        }

        $weightedPrice = $deduplication['weightedPrice'] ?? null;
        if ($weightedPrice !== null) {
            foreach ($grouped as &$groupedLine) {
                $quantity = (int)$groupedLine[$weightedPrice['quantity']];
                if ($quantity > 0) {
                    $amount = (int)round((float)$groupedLine[$weightedPrice['amount']] * 100);
                    $groupedLine[$weightedPrice['field']] = number_format($amount / $quantity / 100, 2, '.', '');
                }
            }
            unset($groupedLine);
        }

        return [...array_values($grouped), ...$unmergeable];
    }

    /**
     * @param array<string, scalar|null> $line Normalised line.
     * @param DeduplicationConfig $deduplication Deduplication rules.
     * @return bool
     */
    private function canAggregateLine(array $line, array $deduplication): bool
    {
        foreach ($deduplication['sum'] ?? [] as $field => $type) {
            if (!isset($line[$field]) || !is_numeric($line[$field])) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param mixed $data Request data.
     * @param string $badgeId Selected badge id.
     * @param \Cake\ORM\Table $linesTable Stock transaction child table.
     * @param LineConfig $config Grid configuration.
     * @return array<string, array{value: string, label: string}>|null
     */
    private function validateSelectors(
        mixed $data,
        string $badgeId,
        Table $linesTable,
        array $config,
    ): ?array {
        if (!is_array($data)) {
            return null;
        }

        $selectors = [];
        foreach ($config['selectors'] ?? [] as $field => $selectorConfig) {
            $value = $this->inputIdentifier($data[$field] ?? null) ?? '';
            $options = $selectorConfig['options'] ?? [];
            if ($value === '' || !isset($options[$value])) {
                return null;
            }

            $associationName = $selectorConfig['association'] ?? '';
            if ($associationName !== '') {
                $target = $linesTable->getAssociation($associationName)->getTarget();
                try {
                    $record = $target->get($value);
                } catch (RecordNotFoundException) {
                    return null;
                }
                                $matchField = $selectorConfig['matchBadgeField'] ?? null;
                if ($matchField !== null && $this->inputIdentifier($record->get($matchField)) !== $badgeId) {
                    return null;
                }
            }

            $selectors[$field] = [
                'value' => $value,
                'label' => $options[$value],
            ];
        }

        return $selectors;
    }

    /**
     * @param mixed $data Request data.
     * @param LineConfig $config Grid configuration.
     * @return array<string, int|string>|null
     */
    private function validateInputValues(mixed $data, array $config): ?array
    {
        if (!is_array($data)) {
            return null;
        }

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
     * @param FieldConfig $config Field configuration.
     * @return string|int|null
     */
    private function calculateValue(array $values, array $config): int|string|null
    {
        $calculation = $config['calculation'] ?? null;
        if (
            $calculation === null
            || $calculation['operation'] !== 'multiply'
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
     * @param FieldConfig $config Field configuration.
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
                $validated < ($config['min'] ?? 0)
                || $validated > ($config['max'] ?? PHP_INT_MAX)
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
     * @param \Cake\ORM\Table $linesTable Child table with a Badges association.
     * @return \App\Model\Table\BadgesTable
     */
    private function badgesTable(Table $linesTable): BadgesTable
    {
        $badges = $linesTable->getAssociation('Badges')->getTarget();
        if (!$badges instanceof BadgesTable) {
            throw new LogicException('The Badges association must use BadgesTable.');
        }

        return $badges;
    }

    /**
     * @param mixed $value Submitted identifier.
     * @return string|null
     */
    private function inputIdentifier(mixed $value): ?string
    {
        return is_string($value) || is_int($value) ? (string)$value : null;
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
            ->withStringBody(json_encode(['error' => $message], JSON_THROW_ON_ERROR));
    }
}
