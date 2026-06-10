<?php
declare(strict_types=1);

namespace App\View\Helper;

use Cake\Datasource\EntityInterface;
use Cake\View\Helper;

class StockTransactionLinesHelper extends Helper
{
    /**
     * @var array<string>
     */
    protected array $helpers = ['Form', 'Number', 'Url'];

    /**
     * @param \Cake\Datasource\EntityInterface $entity Parent entity.
     * @param array<string, string> $badges Badge options.
     * @param array<string, mixed> $config Grid configuration.
     * @return string
     */
    public function grid(EntityInterface $entity, array $badges, array $config): string
    {
        $inputKey = (string)$config['inputKey'];
        $property = (string)($config['property'] ?? $inputKey);
        $lines = $entity->get($property) ?? [];
        $rows = '';

        foreach ($lines as $index => $line) {
            $values = [];
            foreach ($config['fields'] as $field => $fieldConfig) {
                $values[$field] = $line->get($fieldConfig['source'] ?? $field);
            }
            $selectors = [];
            foreach ($config['selectors'] ?? [] as $field => $selectorConfig) {
                $value = (string)$line->get($field);
                $selectors[$field] = [
                    'value' => $value,
                    'label' => (string)($selectorConfig['options'][$value] ?? $value),
                ];
            }
            $badgeId = (string)$line->get('badge_id');
            $rows .= $this->row([
                'inputKey' => $inputKey,
                'badgeId' => $badgeId,
                'badgeName' => $badges[$badgeId] ?? $badgeId,
                'values' => $values,
                'fields' => $config['fields'],
                'selectors' => $selectors,
                'index' => $index,
            ]);
        }

        $selectorControls = '';
        $selectorHeaders = '';
        foreach ($config['selectors'] ?? [] as $field => $selectorConfig) {
            $selectorControls .= '<div class="column">'
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
                $displayValue = !empty($fieldConfig['currency'])
                    ? $this->Number->currency($value, (string)$fieldConfig['currency'])
                    : $value;
                $fieldControls .= '<div class="column"><label>'
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
                if (!empty($fieldConfig['currency'])) {
                    $control = '<div class="stock-line-currency-control"><span aria-hidden="true">'
                        . h($this->currencySymbol((string)$fieldConfig['currency']))
                        . '</span>' . $control . '</div>';
                }
                $fieldControls .= '<div class="column">' . $control . '</div>';
            }
        }

        $headers = '';
        foreach ($config['fields'] as $fieldConfig) {
            $headers .= '<th>' . h($fieldConfig['label']) . '</th>';
        }

        $error = $entity->getError($inputKey);
        $errorMessages = $this->flattenErrors($error);
        $errorHtml = $error
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
        if (is_array($bulkLoader)) {
            $bulkEndpoint = json_encode(
                $this->Url->build($bulkLoader['url']),
                JSON_THROW_ON_ERROR,
            );
            $bulkLoaderHtml = '<div class="row"><div class="column">'
                . $this->Form->control((string)$bulkLoader['field'], [
                    'label' => $bulkLoader['label'],
                    'options' => $bulkLoader['options'],
                    'empty' => $bulkLoader['empty'] ?? __('Select an option'),
                    'value' => '',
                    'data-stock-line-bulk-source' => true,
                ])
                . '</div><div class="column">'
                . '<button type="button" class="button button-outline" data-stock-line-bulk-add>'
                . h($bulkLoader['addLabel'] ?? __('Add'))
                . '</button></div></div>';
        }

        return '<fieldset class="stock-transaction-lines" data-stock-transaction-lines>'
            . '<legend>' . h($config['legend']) . '</legend>'
            . $bulkLoaderHtml
            . (empty($config['hideLineBuilder']) ? '<div class="row"><div class="column">'
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
            . '<p class="error-message" data-stock-line-error hidden></p>'
            . '<div class="table-responsive"><table><thead><tr><th>' . __('Badge') . '</th>'
            . $selectorHeaders . $headers
            . '<th class="actions">' . __('Actions') . '</th></tr></thead>'
            . '<tbody data-stock-line-grid>' . $rows . '</tbody></table></div>'
            . $errorHtml . '</fieldset>'
            . $this->script(
                $endpoint,
                $priceEndpoint,
                $bulkEndpoint,
                $fallbackError,
                $fields,
                $selectors,
            );
    }

    /**
     * @param array<string, mixed> $config Row configuration.
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
                $displayValue = !empty($fieldConfig['currency'])
                    ? $this->Number->currency($value, (string)$fieldConfig['currency'])
                    : (string)$value;
                $cells .= '<td><output class="stock-line-calculated" data-stock-row-output="'
                    . h($field) . '">' . h($displayValue) . '</output>'
                    . sprintf(
                        '<input type="hidden" name="%s" value="%s" data-stock-row-field="%s">',
                        h($inputName),
                        h((string)$value),
                        h($field),
                    ) . '</td>';
            } elseif (!empty($fieldConfig['editable'])) {
                $control = $this->Form->control($inputName, [
                    'label' => false,
                    'type' => 'number',
                    'min' => $fieldConfig['min'] ?? 0,
                    'max' => $fieldConfig['max'] ?? null,
                    'step' => $fieldConfig['step'] ?? 1,
                    'value' => $value,
                    'data-stock-row-field' => $field,
                ]);
                if (!empty($fieldConfig['currency'])) {
                    $control = '<div class="stock-line-currency-control"><span aria-hidden="true">'
                        . h($this->currencySymbol((string)$fieldConfig['currency']))
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
     * @param mixed $errors Validation errors.
     * @return array<string>
     */
    private function flattenErrors(mixed $errors): array
    {
        if (!is_array($errors)) {
            return $errors ? [(string)$errors] : [];
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
     * @param string $fallbackError JSON-encoded fallback error.
     * @param string $fields JSON-encoded field names.
     * @param string $selectors JSON-encoded selector field names.
     * @return string
     */
    private function script(
        string $endpoint,
        string $priceEndpoint,
        string $bulkEndpoint,
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
    var fields = {$fields};
    var fieldNames = Object.keys(fields);
    var selectorNames = {$selectors};
    var currencyFormatter = function (currency) {
        return new Intl.NumberFormat('en-GB', {
            style: 'currency',
            currency: currency
        });
    };
    var nextIndex = grid.querySelectorAll('[data-stock-transaction-line]').length;
    var showError = function (message) {
        error.textContent = message;
        error.hidden = !message;
    };
    var resetFields = function () {
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
    if (badgeInput) badgeInput.addEventListener('change', async function () {
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
    });
    container.addEventListener('click', function (event) {
        var button = event.target.closest('[data-stock-line-remove]');
        if (button) button.closest('[data-stock-transaction-line]').remove();
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
            badgeInput.value = '';
            resetFields();
        } catch (exception) {
            showError(exception.message);
        } finally {
            addButton.disabled = false;
        }
    });
    if (bulkAddButton) bulkAddButton.addEventListener('click', async function () {
        showError('');
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
            if (payload.message) showError(payload.message);
            bulkSource.value = '';
        } catch (exception) {
            showError(exception.message);
        } finally {
            bulkSource.disabled = false;
            bulkAddButton.disabled = false;
        }
    });
})();
</script>
HTML;
    }
}
