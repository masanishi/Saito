<?php
$this->Breadcrumbs->add(__('Users'), '/admin/users');
$this->Breadcrumbs->add(__('user.block.history'), false);
echo $this->Html->tag('h1', __('user.block.history'));

echo $this->element(
    'users/block-report',
    ['mode' => 'full', 'UserBlock' => $UserBlock]
);

$this->Admin->jqueryTable('#blocklist', "[[1, 'desc'], [3, 'desc']]");
