<?php
declare(strict_types=1);

namespace App\View\Helper;

use App\Model\Entity\Audit;
use Cake\Datasource\EntityInterface;
use Cake\View\Helper;
use InvalidArgumentException;

/**
 * @extends \Cake\View\Helper<\App\View\AppView>
 * @property \Cake\View\Helper\FormHelper $Form
 * @property \Cake\View\Helper\NumberHelper $Number
 * @property \Cake\View\Helper\UrlHelper $Url
 * @phpstan-type FieldConfig array{
 *   label: string, source?: string, type?: string, min?: int|float, max?: int|float,
 *   step?: int|float|string, default?: int|float|string, editable?: bool, currency?: string,
 *   target?: string, changes?: array<string, int>,
 *   calculation?: array{operation: string, fields: list<string>}
 * }
 * @phpstan-type SelectorConfig array{label: string, options: array<string, string>, empty?: string}
 * @phpstan-type OrderDetails array{id: string, order_number: string, section: string, user: string, group: string}
 * @phpstan-type GridConfig array{
 *   inputKey: string, property?: string, legend: string, fields: array<string, FieldConfig>,
 *   selectors?: array<string, SelectorConfig>, rowUrl: array<string, mixed>|string,
 *   priceUrl?: array<string, mixed>|string, ajaxError?: string, addLabel?: string,
 *   hideLineBuilder?: bool, rowGroups?: array<string, OrderDetails>,
 *   bulkLoader?: array{
 *     field: string, label: string, options: array<string, string|array<string, string>>, url: array<string, mixed>|string,
 *     empty?: string, addLabel?: string, alerts?: array<string, array{html: string}>,
 *     optionUserIds?: array<string, string>, optionAccountIds?: array<string, string>
 *   }
 * }
 * @phpstan-type RowConfig array{
 *   inputKey: string, badgeId: string, badgeName: string, index: int,
 *   fields: array<string, FieldConfig>, values: array<string, int|float|string|null>,
 *   selectors?: array<string, array{value: string, label: string}>
 * }
 */
class StockTransactionLinesHelper extends Helper
{
    /**
     * @var array<int|string, array<string, mixed>|string>
     */
    protected array $helpers = ['Form', 'Number', 'Url'];

    /**
     * @param \Cake\Datasource\EntityInterface $entity Parent entity.
     * @param array<string, string> $badges Badge options.
     * @param GridConfig $config Grid configuration.
     * @return string
     */
    public function grid(EntityInterface $entity, array $badges, array $config): string
    {
        $inputKey = $config['inputKey'];
        $property = $config['property'] ?? $inputKey;
        $lines = $entity->get($property) ?? [];
        if (!is_iterable($lines)) {
            throw new InvalidArgumentException('Stock transaction lines must be iterable.');
        }
        $rows = '';
        $groupedRows = [];

        foreach ($lines as $index => $line) {
            if (!is_int($index) || !$line instanceof EntityInterface) {
                throw new InvalidArgumentException('Stock transaction lines must be indexed entities.');
            }
            $values = [];
            foreach ($config['fields'] as $field => $fieldConfig) {
                $values[$field] = $this->fieldValue($line->get($fieldConfig['source'] ?? $field));
            }
            $selectors = [];
            foreach ($config['selectors'] ?? [] as $field => $selectorConfig) {
                $value = $this->identifier($line->get($field));
                $selectors[$field] = [
                    'value' => $value,
                    'label' => $selectorConfig['options'][$value] ?? $value,
                ];
            }
            $badgeId = $this->identifier($line->get('badge_id'));
            $row = $this->row([
                'inputKey' => $inputKey,
                'badgeId' => $badgeId,
                'badgeName' => $badges[$badgeId] ?? $badgeId,
                'values' => $values,
                'fields' => $config['fields'],
                'selectors' => $selectors,
                'index' => $index,
            ]);
            $rowGroups = $config['rowGroups'] ?? [];
            $group = $rowGroups[$selectors['order_line_id']['value'] ?? ''] ?? null;
            if ($group !== null) {
                $groupId = $group['id'];
                if (!isset($groupedRows[$groupId])) {
                    $groupedRows[$groupId] = ['details' => $group, 'rows' => ''];
                }
                $groupedRows[$groupId]['rows'] .= $row;
            } else {
                $rows .= $row;
            }
        }

        $selectorControls = '';
        $selectorHeaders = '';
        foreach ($config['selectors'] ?? [] as $field => $selectorConfig) {
            $selectorControls .= '<div class="column stock-line-builder-column">'
                . $this->Form->control('line_' . $field, [
                    'label' => $selectorConfig['label'],
                    'options' => $selectorConfig['options'],
                    'empty' => $selectorConfig['empty'] ?? __('Select an option'),
                    'value' => '',
                    'data-stock-line-selector' => $field,
                ])
                . '</div>';
            $selectorHeaders .= '<th>' . h($selectorConfig['label']) . '</th>';
        }

        $fieldControls = '';
        foreach ($config['fields'] as $field => $fieldConfig) {
            if (isset($fieldConfig['calculation'])) {
                $value = (string)($fieldConfig['default'] ?? '0.00');
                $displayValue = isset($fieldConfig['currency']) && $fieldConfig['currency'] !== ''
                    ? $this->Number->currency($value, $fieldConfig['currency'])
                    : $value;
                $fieldControls .= '<div class="column stock-line-builder-column"><label>'
                    . h($fieldConfig['label'])
                    . '</label><output class="stock-line-calculated" data-stock-line-output="'
                    . h($field) . '">' . h($displayValue) . '</output>'
                    . '<input type="hidden" name="line_' . h($field) . '" value="'
                    . h($value) . '" data-stock-line-field="' . h($field) . '"></div>';
            } else {
                $control = $this->Form->control('line_' . $field, [
                    'label' => $fieldConfig['label'],
                    'type' => 'number',
                    'min' => $fieldConfig['min'] ?? 0,
                    'max' => $fieldConfig['max'] ?? null,
                    'step' => $fieldConfig['step'] ?? 1,
                    'value' => $fieldConfig['default'] ?? 0,
                    'data-stock-line-field' => $field,
                ]);
                if (isset($fieldConfig['currency']) && $fieldConfig['currency'] !== '') {
                    $control = '<div class="stock-line-currency-control"><span aria-hidden="true">'
                        . h($this->currencySymbol($fieldConfig['currency']))
                        . '</span>' . $control . '</div>';
                }
                $fieldControls .= '<div class="column stock-line-builder-column">'
                    . $control . '</div>';
            }
        }

        $headers = '';
        foreach ($config['fields'] as $fieldConfig) {
            $headers .= '<th>' . h($fieldConfig['label']) . '</th>';
        }

        $error = $entity->getError($inputKey);
        $errorMessages = $this->flattenErrors($error);
        $errorHtml = $error !== []
            ? '<p class="error-message">' . h(implode(' ', $errorMessages)) . '</p>'
            : '';
        $endpoint = json_encode($this->Url->build($config['rowUrl']), JSON_THROW_ON_ERROR);
        $priceEndpoint = isset($config['priceUrl'])
            ? json_encode($this->Url->build($config['priceUrl']), JSON_THROW_ON_ERROR)
            : 'null';
        $fallbackError = json_encode(
            $config['ajaxError'] ?? __('Unable to add the stock transaction line.'),
            JSON_THROW_ON_ERROR,
        );
        $fields = json_encode($config['fields'], JSON_THROW_ON_ERROR);
        $selectors = json_encode(array_keys($config['selectors'] ?? []), JSON_THROW_ON_ERROR);
        $bulkLoader = $config['bulkLoader'] ?? null;
        $bulkLoaderHtml = '';
        $bulkEndpoint = 'null';
        $bulkAlerts = '[]';
        $bulkOptionUserIds = '[]';
        $bulkOptionAccountIds = '[]';
        if ($bulkLoader !== null) {
            $bulkEndpoint = json_encode(
                $this->Url->build($bulkLoader['url']),
                JSON_THROW_ON_ERROR,
            );
            $bulkAlerts = json_encode($bulkLoader['alerts'] ?? [], JSON_THROW_ON_ERROR);
            $bulkOptionUserIds = json_encode($bulkLoader['optionUserIds'] ?? [], JSON_THROW_ON_ERROR);
            $bulkOptionAccountIds = json_encode($bulkLoader['optionAccountIds'] ?? [], JSON_THROW_ON_ERROR);
            $bulkLoaderHtml = '<div class="row stock-line-bulk-loader"><div class="column stock-line-bulk-source">'
                . $this->Form->control($bulkLoader['field'], [
                    'label' => $bulkLoader['label'],
                    'options' => $bulkLoader['options'],
                    'empty' => $bulkLoader['empty'] ?? __('Select an option'),
                    'value' => '',
                    'data-stock-line-bulk-source' => true,
                ])
                . '</div><div class="column stock-line-bulk-action">'
                . '<button type="button" class="button button-outline" data-stock-line-bulk-add>'
                . h($bulkLoader['addLabel'] ?? __('Add'))
                . '</button></div></div>';
        }

        return '<fieldset class="stock-transaction-lines" data-stock-transaction-lines>'
            . '<legend>' . h($config['legend']) . '</legend>'
            . $bulkLoaderHtml
            . (!($config['hideLineBuilder'] ?? false)
                ? '<div class="row stock-line-builder"><div class="column stock-line-badge-column">'
            . $this->Form->control('line_badge_id', [
                'label' => __('Badge'),
                'options' => $badges,
                'empty' => __('Select a badge'),
                'value' => '',
                'data-stock-line-badge' => true,
            ])
            . '</div>' . $selectorControls . $fieldControls . '</div>'
            . '<button type="button" class="button button-outline" data-stock-line-add>'
            . h($config['addLabel'] ?? __('Add Line')) . '</button>' : '')
            . '<div class="stock-line-alerts" data-stock-line-alerts hidden></div>'
            . '<p class="error-message" data-stock-line-error hidden></p>'
            . $this->gridTables($config, $selectorHeaders, $headers, $rows, $groupedRows)
            . $errorHtml . '</fieldset>'
            . $this->script(
                $endpoint,
                $priceEndpoint,
                $bulkEndpoint,
                $bulkAlerts,
                $bulkOptionUserIds,
                $bulkOptionAccountIds,
                $fallbackError,
                $fields,
                $selectors,
            );
    }

    /**
     * Render persisted audit transactions using the shared stock grid structure.
     * Audit rows are saved individually, so they intentionally do not use the
     * nested editable inputs produced by grid().
     *
     * @param \App\Model\Entity\Audit $audit Audit with AuditLines.Badges loaded.
     * @return string
     */
    public function auditGrid(Audit $audit): string
    {
        $editable = !$audit->audit_completed;
        $rows = '';

        foreach ($audit->audit_lines ?? [] as $line) {
            $expected = (int)$line->audit_expected_quantity;
            $actual = (int)$line->audit_actual_quantity;
            $difference = $actual - $expected;
            $badgeName = $line->badge->badge_name;
            $action = '';
            if ($editable) {
                $action = sprintf(
                    '<td class="actions"><button type="button" '
                    . 'class="button button-outline audit-count-button" '
                    . 'data-badge-id="%s" data-badge-name="%s" '
                    . 'data-expected="%d" data-actual="%d">%s</button></td>',
                    h($line->badge_id),
                    h($badgeName),
                    $expected,
                    $actual,
                    __('Count'),
                );
            }
            $rows .= sprintf(
                '<tr data-created="%s" data-badge="%s" data-expected="%d" '
                . 'data-actual="%d" data-difference="%d"><td>%s</td><td>%d</td>'
                . '<td>%d</td><td><strong>%s</strong></td>%s</tr>',
                h($line->transaction_timestamp->format('Y-m-d H:i:s.u')),
                h(mb_strtolower($badgeName)),
                $expected,
                $actual,
                $difference,
                h($badgeName),
                $expected,
                $actual,
                h(sprintf('%+d', $difference)),
                $action,
            );
        }

        $actionHeader = $editable ? '<th class="actions">' . __('Actions') . '</th>' : '';

        return '<div class="table-responsive"><table class="stock-transaction-grid audit-lines-table">'
            . '<thead><tr>'
            . $this->auditSortHeader('badge', 'text', __('Badge'))
            . $this->auditSortHeader('expected', 'number', __('Expected'))
            . $this->auditSortHeader('actual', 'number', __('Actual'))
            . $this->auditSortHeader('difference', 'number', __('Difference'))
            . $actionHeader
            . '</tr></thead><tbody id="audit-lines-body">' . $rows . '</tbody></table></div>';
    }

    /**
     * @param string $key Sort key.
     * @param string $type Sort value type.
     * @param string $label Heading label.
     * @return string
     */
    private function auditSortHeader(string $key, string $type, string $label): string
    {
        return sprintf(
            '<th><button type="button" class="audit-sort" data-audit-sort="%s" '
            . 'data-sort-type="%s">%s</button></th>',
            h($key),
            h($type),
            h($label),
        );
    }

    /**
     * @param RowConfig $config Row configuration.
     * @return string
     */
    public function row(array $config): string
    {
        $selectorCells = '';
        foreach ($config['selectors'] ?? [] as $field => $selector) {
            $selectorCells .= sprintf(
                '<td>%s<input type="hidden" name="%s[%d][%s]" value="%s"></td>',
                h($selector['label']),
                h($config['inputKey']),
                $config['index'],
                h($field),
                h($selector['value']),
            );
        }

        $cells = '';
        foreach ($config['fields'] as $field => $fieldConfig) {
            $value = $config['values'][$field];
            $inputName = sprintf(
                '%s[%d][%s]',
                $config['inputKey'],
                $config['index'],
                $field,
            );
            if (isset($fieldConfig['calculation'])) {
                $displayValue = isset($fieldConfig['currency']) && $fieldConfig['currency'] !== ''
                    ? $this->Number->currency($value, $fieldConfig['currency'])
                    : (string)$value;
                $cells .= '<td><output class="stock-line-calculated" data-stock-row-output="'
                    . h($field) . '">' . h($displayValue) . '</output>'
                    . sprintf(
                        '<input type="hidden" name="%s" value="%s" data-stock-row-field="%s">',
                        h($inputName),
                        h((string)$value),
                        h($field),
                    ) . '</td>';
            } elseif ($fieldConfig['editable'] ?? false) {
                $control = $this->Form->control($inputName, [
                    'label' => false,
                    'type' => 'number',
                    'min' => $fieldConfig['min'] ?? 0,
                    'max' => $fieldConfig['max'] ?? null,
                    'step' => $fieldConfig['step'] ?? 1,
                    'value' => $value,
                    'data-stock-row-field' => $field,
                ]);
                if (isset($fieldConfig['currency']) && $fieldConfig['currency'] !== '') {
                    $control = '<div class="stock-line-currency-control"><span aria-hidden="true">'
                        . h($this->currencySymbol($fieldConfig['currency']))
                        . '</span>' . $control . '</div>';
                }
                $cells .= '<td>' . $control . '</td>';
            } else {
                $cells .= '<td>' . $this->Number->format($value)
                    . sprintf(
                        '<input type="hidden" name="%s" value="%s" data-stock-row-field="%s">',
                        h($inputName),
                        h((string)$value),
                        h($field),
                    ) . '</td>';
            }
        }

        return sprintf(
            '<tr data-stock-transaction-line><td>%s'
            . '<input type="hidden" name="%s[%d][badge_id]" value="%s"></td>%s%s'
            . '<td><button type="button" class="button button-outline" data-stock-line-remove>%s</button></td></tr>',
            h($config['badgeName']),
            h($config['inputKey']),
            $config['index'],
            h($config['badgeId']),
            $selectorCells,
            $cells,
            __('Remove'),
        );
    }

    /**
     * Render a separately labelled table for one source order.
     *
     * @param OrderDetails $details Order details.
     * @param array{fields: array<string, array{label: string}>, selectors?: array<string, array{label: string}>} $config Grid configuration.
     * @param string $rows Table rows.
     * @return string
     */
    public function orderGroup(array $details, array $config, string $rows): string
    {
        $selectorHeaders = '';
        foreach ($config['selectors'] ?? [] as $selectorConfig) {
            $selectorHeaders .= '<th>' . h($selectorConfig['label']) . '</th>';
        }
        $headers = '';
        foreach ($config['fields'] as $fieldConfig) {
            $headers .= '<th>' . h($fieldConfig['label']) . '</th>';
        }

        return $this->orderGroupTable($details, $selectorHeaders, $headers, $rows);
    }

    /**
     * @param GridConfig $config Grid configuration.
     * @param array<string, array{details: OrderDetails, rows: string}> $groupedRows Grouped rows.
     * @return string
     */
    private function gridTables(
        array $config,
        string $selectorHeaders,
        string $headers,
        string $rows,
        array $groupedRows,
    ): string {
        if (!isset($config['rowGroups'])) {
            return '<div class="table-responsive"><table class="stock-transaction-grid">'
                . '<thead><tr><th>' . __('Badge') . '</th>' . $selectorHeaders . $headers
                . '<th class="actions">' . __('Actions') . '</th></tr></thead>'
                . '<tbody data-stock-line-grid>' . $rows . '</tbody></table></div>';
        }

        $tables = '';
        foreach ($groupedRows as $group) {
            $tables .= $this->orderGroupTable(
                $group['details'],
                $selectorHeaders,
                $headers,
                $group['rows'],
            );
        }

        return '<div class="stock-line-order-groups" data-stock-line-grid>' . $tables . '</div>';
    }

    /**
     * @param OrderDetails $details Order details.
     * @return string
     */
    private function orderGroupTable(array $details, string $selectorHeaders, string $headers, string $rows): string
    {
        return '<section class="stock-line-order-group" data-stock-line-order="'
            . h($details['id']) . '"><header><h2>' . h(__('Order {0}', $details['order_number']))
            . '</h2><dl><div><dt>' . __('Section') . '</dt><dd>' . h($details['section'])
            . '</dd></div><div><dt>' . __('User') . '</dt><dd>' . h($details['user'])
            . '</dd></div><div><dt>' . __('Group') . '</dt><dd>' . h($details['group'])
            . '</dd></div></dl></header><div class="table-responsive"><table class="stock-transaction-grid">'
            . '<thead><tr><th>' . __('Badge') . '</th>' . $selectorHeaders . $headers
            . '<th class="actions">' . __('Actions') . '</th></tr></thead><tbody>'
            . $rows . '</tbody></table></div></section>';
    }

    /**
     * @param string $currency ISO 4217 currency code.
     * @return string
     */
    private function currencySymbol(string $currency): string
    {
        return match ($currency) {
            'GBP' => '£',
            'EUR' => '€',
            'USD' => '$',
            default => $currency,
        };
    }

    /**
     * @param mixed $value Entity field value.
     * @return string|float|int|null
     */
    private function fieldValue(mixed $value): int|float|string|null
    {
        if ($value === null || is_int($value) || is_float($value) || is_string($value)) {
            return $value;
        }

        throw new InvalidArgumentException('Stock transaction values must be numbers, strings or null.');
    }

    /**
     * @param mixed $value Entity identifier.
     * @return string
     */
    private function identifier(mixed $value): string
    {
        if ($value === null || is_string($value) || is_int($value)) {
            return (string)$value;
        }

        throw new InvalidArgumentException('Stock transaction identifiers must be strings or integers.');
    }

    /**
     * @param mixed $errors Validation errors.
     * @return array<string>
     */
    private function flattenErrors(mixed $errors): array
    {
        if (!is_array($errors)) {
            return is_scalar($errors) && (string)$errors !== '' ? [(string)$errors] : [];
        }

        $messages = [];
        array_walk_recursive($errors, function ($message) use (&$messages): void {
            if (is_scalar($message)) {
                $messages[] = (string)$message;
            }
        });

        return $messages;
    }

    /**
     * @param string $endpoint JSON-encoded endpoint.
     * @param string $priceEndpoint JSON-encoded price endpoint.
     * @param string $bulkEndpoint JSON-encoded bulk loader endpoint.
     * @param string $bulkAlerts JSON-encoded alerts keyed by bulk-source option.
     * @param string $bulkOptionUserIds JSON-encoded user IDs keyed by bulk-source option.
     * @param string $bulkOptionAccountIds JSON-encoded account IDs keyed by bulk-source option.
     * @param string $fallbackError JSON-encoded fallback error.
     * @param string $fields JSON-encoded field names.
     * @param string $selectors JSON-encoded selector field names.
     * @return string
     */
    private function script(
        string $endpoint,
        string $priceEndpoint,
        string $bulkEndpoint,
        string $bulkAlerts,
        string $bulkOptionUserIds,
        string $bulkOptionAccountIds,
        string $fallbackError,
        string $fields,
        string $selectors,
    ): string {
        return <<<HTML
<script>
(function () {
    var container = document.currentScript.previousElementSibling;
    while (container && !container.matches('[data-stock-transaction-lines]')) {
        container = container.previousElementSibling;
    }
    if (!container) return;
    var addButton = container.querySelector('[data-stock-line-add]');
    var badgeInput = container.querySelector('[data-stock-line-badge]');
    var bulkSource = container.querySelector('[data-stock-line-bulk-source]');
    var bulkAddButton = container.querySelector('[data-stock-line-bulk-add]');
    var grid = container.querySelector('[data-stock-line-grid]');
    var error = container.querySelector('[data-stock-line-error]');
    var csrfInput = document.querySelector('input[name="_csrfToken"]');
    var priceEndpoint = {$priceEndpoint};
    var bulkEndpoint = {$bulkEndpoint};
    var bulkAlerts = {$bulkAlerts};
    var bulkOptionUserIds = {$bulkOptionUserIds};
    var bulkOptionAccountIds = {$bulkOptionAccountIds};
    var fields = {$fields};
    var fieldNames = Object.keys(fields);
    var selectorNames = {$selectors};
    var badgeSelect = null;
    if (badgeInput && window.jQuery && window.jQuery.fn.select2) {
        badgeSelect = window.jQuery(badgeInput);
        badgeSelect.select2({
            width: '100%',
            placeholder: badgeInput.options[0] ? badgeInput.options[0].text : '',
            allowClear: true
        });
    }
    var currencyFormatter = function (currency) {
        return new Intl.NumberFormat('en-GB', {
            style: 'currency',
            currency: currency
        });
    };
    var nextIndex = grid.querySelectorAll('[data-stock-transaction-line]').length;
    var alerts = container.querySelector('[data-stock-line-alerts]');
    var showError = function (message) {
        error.textContent = message;
        error.hidden = !message;
    };
    var showAlerts = function (items) {
            alerts.replaceChildren();
            (Array.isArray(items) ? items : []).forEach(function (item) {
                if (item.html) {
                    var template = document.createElement('template');
                    template.innerHTML = item.html;
                    alerts.appendChild(template.content.cloneNode(true));
                    return;
                }
                var alert = document.createElement('div');
                alert.className = 'message ' + (item.level === 'danger'
                ? 'error non-district-email-alert'
                : (item.level === 'warning' ? 'warning' : 'info'));
            var title = document.createElement('strong');
            title.textContent = item.title;
            alert.appendChild(title);
            alert.appendChild(document.createElement('br'));
            alert.appendChild(document.createTextNode(item.message));
            alerts.appendChild(alert);
        });
        alerts.hidden = alerts.childElementCount === 0;
    };
    var activeBulkUserId = null;
    var activeBulkAccountId = null;
    var selectedBulkOrderIds = function () {
        return Array.from(grid.querySelectorAll('[data-stock-line-order]')).map(function (order) {
            return order.getAttribute('data-stock-line-order');
        });
    };
    var showBulkAlerts = function (extraAlerts, previewOrderId) {
        var alerts = [];
        var orderIds = selectedBulkOrderIds();
        if (previewOrderId && !orderIds.includes(previewOrderId)) {
            orderIds.push(previewOrderId);
        }
        orderIds.forEach(function (orderId) {
            if (bulkAlerts[orderId]) alerts.push(bulkAlerts[orderId]);
        });
        (Array.isArray(extraAlerts) ? extraAlerts : []).forEach(function (alert) {
            if (!alert.html || !bulkAlerts[previewOrderId] || alert.html !== bulkAlerts[previewOrderId].html) {
                alerts.push(alert);
            }
        });
        showAlerts(alerts);
    };
    var updateBulkOptions = function () {
        if (!bulkSource) return;
        bulkSource.querySelectorAll('option').forEach(function (option) {
            if (!option.value) return;
            var eligible = !activeBulkUserId
                || (bulkOptionUserIds[option.value] === activeBulkUserId
                    && bulkOptionAccountIds[option.value] === activeBulkAccountId);
            option.hidden = !eligible;
            option.disabled = !eligible;
        });
        bulkSource.querySelectorAll('optgroup').forEach(function (group) {
            group.hidden = !Array.from(group.querySelectorAll('option')).some(function (option) {
                return !option.hidden;
            });
        });
    };
    var resetFields = function () {
        if (badgeInput) {
            badgeInput.value = '';
            if (badgeSelect) badgeSelect.val(null).trigger('change.select2');
        }
        fieldNames.forEach(function (field) {
            var input = container.querySelector('[data-stock-line-field="' + field + '"]');
            if (input) input.value = fields[field].default ?? 0;
        });
        selectorNames.forEach(function (field) {
            var input = container.querySelector('[data-stock-line-selector="' + field + '"]');
            if (input) input.value = '';
        });
        calculate(container, 'data-stock-line-field');
    };
    var calculate = function (scope, selectorPrefix) {
        fieldNames.forEach(function (field) {
            var calculation = fields[field].calculation;
            if (!calculation || calculation.operation !== 'multiply') return;
            var result = calculation.fields.reduce(function (total, sourceField) {
                var input = scope.querySelector(
                    '[' + selectorPrefix + '="' + sourceField + '"]'
                );
                return total * Number(input ? input.value : 0);
            }, 1);
            var target = scope.querySelector('[' + selectorPrefix + '="' + field + '"]');
            if (target) {
                target.value = result.toFixed(2);
                var outputPrefix = selectorPrefix === 'data-stock-line-field'
                    ? 'data-stock-line-output'
                    : 'data-stock-row-output';
                var output = scope.querySelector('[' + outputPrefix + '="' + field + '"]');
                if (output) {
                    output.textContent = fields[field].currency
                        ? currencyFormatter(fields[field].currency).format(Number(target.value))
                        : target.value;
                }
            }
        });
    };
    calculate(container, 'data-stock-line-field');
    grid.querySelectorAll('[data-stock-transaction-line]').forEach(function (row) {
        calculate(row, 'data-stock-row-field');
    });
    container.addEventListener('input', function (event) {
        if (event.target.matches('[data-stock-line-field]')) {
            calculate(container, 'data-stock-line-field');
        }
        var row = event.target.closest('[data-stock-transaction-line]');
        if (row) calculate(row, 'data-stock-row-field');
    });
    var loadBadgePrice = async function () {
        if (!priceEndpoint || !badgeInput.value) return;
        showError('');
        try {
            var url = new URL(priceEndpoint, window.location.origin);
            url.searchParams.set('badge_id', badgeInput.value);
            var response = await fetch(url, {headers: {'Accept': 'application/json'}});
            var payload = await response.json();
            if (!response.ok) throw new Error(payload.error || {$fallbackError});
            var unitPrice = container.querySelector('[data-stock-line-field="unit_price"]');
            if (unitPrice) unitPrice.value = payload.unit_price;
            calculate(container, 'data-stock-line-field');
        } catch (exception) {
            showError(exception.message);
        }
    };
    if (badgeInput) {
        if (badgeSelect) {
            badgeSelect.on('change', loadBadgePrice);
        } else {
            badgeInput.addEventListener('change', loadBadgePrice);
        }
    }
    container.addEventListener('click', function (event) {
        var button = event.target.closest('[data-stock-line-remove]');
        if (button) {
            var row = button.closest('[data-stock-transaction-line]');
            var orderGroup = row.closest('[data-stock-line-order]');
            row.remove();
            if (orderGroup && !orderGroup.querySelector('[data-stock-transaction-line]')) {
                orderGroup.remove();
            }
            if (!grid.querySelector('[data-stock-line-order]')) {
                activeBulkUserId = null;
                activeBulkAccountId = null;
            }
            updateBulkOptions();
            showBulkAlerts();
        }
    });
    if (addButton) addButton.addEventListener('click', async function () {
        showError('');
        addButton.disabled = true;
        var data = new FormData();
        data.append('badge_id', badgeInput.value);
        data.append('index', String(nextIndex));
        selectorNames.forEach(function (field) {
            data.append(
                field,
                container.querySelector('[data-stock-line-selector="' + field + '"]').value
            );
        });
        fieldNames.forEach(function (field) {
            data.append(field, container.querySelector('[data-stock-line-field="' + field + '"]').value);
        });
        if (csrfInput) data.append('_csrfToken', csrfInput.value);
        try {
            var response = await fetch({$endpoint}, {
                method: 'POST',
                body: data,
                headers: {'Accept': 'application/json'}
            });
            var payload = await response.json();
            if (!response.ok) throw new Error(payload.error || {$fallbackError});
            grid.insertAdjacentHTML('beforeend', payload.html);
            calculate(grid.lastElementChild, 'data-stock-row-field');
            nextIndex += 1;
            resetFields();
        } catch (exception) {
            showError(exception.message);
        } finally {
            addButton.disabled = false;
        }
    });
    if (bulkAddButton) bulkAddButton.addEventListener('click', async function () {
        showError('');
        showBulkAlerts();
        if (!bulkSource.value) return;
        var existingOrderLineIds = Array.from(
            grid.querySelectorAll('input[name$="[order_line_id]"]')
        ).map(function (input) {
            return input.value;
        });
        var existingBadgeQuantities = {};
        grid.querySelectorAll('[data-stock-transaction-line]').forEach(function (row) {
            var badge = row.querySelector('input[name$="[badge_id]"]');
            var quantity = row.querySelector('[data-stock-row-field="quantity"]');
            if (!badge || !quantity) return;
            existingBadgeQuantities[badge.value] =
                (existingBadgeQuantities[badge.value] || 0) + Number(quantity.value);
        });
        bulkSource.disabled = true;
        bulkAddButton.disabled = true;
        try {
            var url = new URL(bulkEndpoint, window.location.origin);
            url.searchParams.set(bulkSource.name, bulkSource.value);
            url.searchParams.set('index', String(nextIndex));
            existingOrderLineIds.forEach(function (orderLineId) {
                url.searchParams.append('existing_order_line_ids[]', orderLineId);
            });
            Object.keys(existingBadgeQuantities).forEach(function (badgeId) {
                url.searchParams.set(
                    'existing_badge_quantities[' + badgeId + ']',
                    String(existingBadgeQuantities[badgeId])
                );
            });
            var response = await fetch(url, {headers: {'Accept': 'application/json'}});
            var payload = await response.json();
            if (!response.ok) throw new Error(payload.error || {$fallbackError});
            grid.insertAdjacentHTML('beforeend', payload.html);
            grid.querySelectorAll('[data-stock-transaction-line]').forEach(function (row) {
                calculate(row, 'data-stock-row-field');
            });
            nextIndex = payload.next_index;
            activeBulkUserId = payload.html ? payload.user_id : activeBulkUserId;
            activeBulkAccountId = payload.html ? payload.account_id : activeBulkAccountId;
            updateBulkOptions();
            showBulkAlerts(payload.alerts, bulkSource.value);
            container.dispatchEvent(new CustomEvent('stock-lines:bulk-loaded', {detail: payload}));
            bulkSource.value = '';
        } catch (exception) {
            showError(exception.message);
        } finally {
            bulkSource.disabled = false;
            bulkAddButton.disabled = false;
        }
    });
    if (bulkSource) bulkSource.addEventListener('change', function () {
        showBulkAlerts([], bulkSource.value);
    });
})();
</script>
HTML;
    }
}
