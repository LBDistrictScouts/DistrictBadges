<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Replenishment $replenishment
 * @var array<string, array<string, mixed>> $receiptRows
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(
                __('View Replenishment'),
                ['action' => 'view', $replenishment->id],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->Html->link(
                __('List Replenishments'),
                ['action' => 'index'],
                ['class' => 'side-nav-item'],
            ) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="replenishments form content">
            <?= $this->Form->create($replenishment) ?>
            <fieldset>
                <legend>
                    <?= __('Receive Replenishment {0}', $replenishment->replenishment_number) ?>
                </legend>
                <p>
                    <?= __('Enter the quantity received. Leave a field blank to record zero items.') ?>
                </p>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th><?= __('Badge') ?></th>
                                <th><?= __('Expected') ?></th>
                                <th><?= __('Received') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($receiptRows as $orderLineId => $row) : ?>
                            <tr>
                                <td><?= h($row['badge_name']) ?></td>
                                <td data-expected-quantity="<?= h((string)$row['expected_quantity']) ?>">
                                    <?= $this->Number->format($row['expected_quantity']) ?>
                                </td>
                                <td>
                                    <?= $this->Form->control(
                                        "receipt_lines.{$orderLineId}.quantity",
                                        [
                                            'label' => false,
                                            'type' => 'number',
                                            'min' => 0,
                                            'step' => 1,
                                            'value' => '',
                                            'placeholder' => '0',
                                        ],
                                    ) ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </fieldset>
            <?= $this->Form->button(__('Record Receipt')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
