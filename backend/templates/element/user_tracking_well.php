<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Datasource\EntityInterface $entity
 * @var string|null $heading
 * @var string|null $headingId
 */
$heading ??= __('History');
$headingId ??= 'record-history-heading';
$createdDate = $entity->get('created_date');
$updatedDate = $entity->get('updated_date');
$createdBy = $entity->get('created_by_user');
$updatedBy = $entity->get('updated_by_user');
?>
<section class="related record-history-well" aria-labelledby="<?= h($headingId) ?>">
    <h4 id="<?= h($headingId) ?>"><?= h($heading) ?></h4>
    <dl>
        <div>
            <dt><?= __('Created') ?></dt>
            <dd><?= $createdDate
                ? h($createdDate->i18nFormat('dd MMM yyyy HH:mm'))
                : __('Not recorded') ?></dd>
        </div>
        <div>
            <dt><?= __('Created by') ?></dt>
            <dd><?= $createdBy ? h($createdBy->get('full_name')) : __('Not recorded') ?></dd>
        </div>
        <div>
            <dt><?= __('Last modified') ?></dt>
            <dd><?= $updatedDate
                ? h($updatedDate->i18nFormat('dd MMM yyyy HH:mm'))
                : __('Not recorded') ?></dd>
        </div>
        <div>
            <dt><?= __('Modified by') ?></dt>
            <dd><?= $updatedBy ? h($updatedBy->get('full_name')) : __('Not recorded') ?></dd>
        </div>
    </dl>
</section>
