<?php

use Cake\Utility\Text;

$this->start('headerSubnavLeft');
echo $this->Layout->navbarBack();
$this->end();

$this->element('users/menu');

$urlToHistory = [
    'controller' => 'searches',
    'action' => 'advanced',
    '?' => ['name' => $user->get('username')]
];

$table = [
    [
        __('username_marking'),
        h(
            $user->get('username')
        ) . " <span class='infoText'>({$this->User->type($user->get('user_type'))})</span>",
        # @td user_type for mod and admin
    ]
];

$table[] = [
    __('user.avatar.t'),
    $this->User->getAvatar($user, ['link' => false])
];

if (!$user->isActivated() && $CurrentUser->permission('saito.core.user.activate')) {
    $table[] = [
        h(__('user.actv.t')),
        h(__('user.actv.ny'))
    ];
}

if ($user->isLocked()) {
    $table[] = [
        __('user.set.lock.t'),
        $this->User->banned(true),
    ];
}

if ($user->get('user_real_name')) {
    $table[] = [
        __('user_real_name'),
        h($user->get('user_real_name'))
    ];
}

$viewContactPermission = $CurrentUser->permission('saito.core.user.view.contact');
if ($user->get('user_email') && ($user->get('personal_messages') || $viewContactPermission)) {
    $concat = $this->Html->link(
        '<i class="fa fa-envelope-o fa-lg"></i>',
        ['controller' => 'contacts', 'action' => 'user', $user['id']],
        ['escape' => false]
    );
    if ($viewContactPermission) {
        $text = '(' . $this->Text->autoLinkEmails($user->get('user_email')) . ')';
        $concat .= ' ' . $this->Layout->infoText($text);
    }
    $table[] = [__('Contact'), $concat];
}

if ($user->get('user_hp')) {
    $table[] = [
        __('user_hp'),
        $this->User->linkExternalHomepage($user->get('user_hp'))
    ];
}

if ($user->get('user_place')) {
    $table[] = [
        __('user_place'),
        h($user->get('user_place'))
    ];
}

$table[] = [
    __('user_since'),
    $this->TimeH->formatTime(
        $user->get('registered'),
        '%d.%m.%Y'
    )
];

$table[] = [
    __('user_postings'),
    $this->Html->link(
        $user->numberOfPostings(),
        $urlToHistory,
        ['escape' => false]
    )
];

// helpful postings
if ($solved) {
    $table[] = [
        $this->Posting->solvedBadge(),
        $solved
    ];
}

// ignoredBy
$ignoreHelp = $this->SaitoHelp->icon(7);
if ($user->get('ignore_count') > 0) {
    $table[] = [
        __('user_ignored_by'),
        $user->get('ignore_count') . $ignoreHelp
    ];
    $ignoreHelp = '';
}

// ignores
if ($user->get('ignores') && count($user->get('ignores')->toArray())) {
    $o = [];
    foreach ($user->get('ignores') as $ignoredUser) {
        $ui = $this->Form->postLink(
            $this->Layout->textWithIcon('', 'eye-slash'),
            ['action' => 'unignore'],
            ['data' => ['id' => $ignoredUser->get('id')], 'escape' => false]
        );
        $l = $this->User->linkToUserProfile(
            $ignoredUser,
            $CurrentUser
        );
        $o[] = "$ui &nbsp; $l";
    }
    $table[] = [
        __('user_ignores'),
        $this->Html->nestedList($o) . $ignoreHelp
    ];
}

// profile
if ($user->get('profile')) {
    $table[] = [
        __('user_profile'),
        $this->Parser->parse($user->get('profile'))
    ];
}

if ($user->get('signature')) {
    $table[] = [
        __('user_signature'),
        $this->Parser->parse($user->get('signature'), ['embed' => false])
    ];
}

//= get additional profile info from plugins
$items = $SaitoEventManager->dispatch(
    'Request.Saito.View.User.beforeFullProfile',
    ['user' => $user, 'View' => $this]
);
if ($items) {
    foreach ($items as $item) {
        $table[] = [$item['title'], $item['content']];
    }
}
?>
<div class="users view">
    <div class="card mb-3">
        <div class="card-header">
            <?= $this->Layout->panelHeading(__('user.b.profile')) ?>
        </div>
        <div class="card-body">
            <table class='table th-left elegant'>
                <?= $this->Html->tableCells($table); ?>
            </table>
        </div>

        <?php
        $isLoggedIn = $CurrentUser->isLoggedIn();
        $isUsersEntry = $CurrentUser->isUser($user);

        $panel = '';
        if ($isUsersEntry) {
            $panel .= $this->Html->link(
                __('edit_userdata'),
                ['action' => 'edit', $user->get('id')],
                [
                    'id' => 'btn_user_edit',
                    'class' => 'btn btn-primary'
                ]
            );
        }
        if ($isLoggedIn && !$isUsersEntry) {
            if ($CurrentUser->ignores($user->get('id'))) {
                $panel .= $this->Form->postLink(
                    $this->Layout->textWithIcon(
                        h(__('unignore_this_user')),
                        'eye-slash'
                    ),
                    ['action' => 'unignore'],
                    [
                        'class' => 'btn shp',
                        'data' => ['id' => $user->get('id')],
                        'data-shpid' => 7,
                        'escape' => false
                    ]
                );
            } else {
                $panel .= $this->Form->postLink(
                    $this->Layout->textWithIcon(
                        h(__('ignore_this_user')),
                        'eye-slash'
                    ),
                    ['action' => 'ignore'],
                    [
                        'class' => 'btn shp',
                        'data' => ['id' => $user->get('id')],
                        'data-shpid' => 7,
                        'escape' => false
                    ]
                );
            }

            $menuItems = [];

            if ($CurrentUser->permission('saito.core.user.edit')) {
                // edit user
                $menuItems[] = $this->Html->link(
                    '<i class="fa fa-pencil"></i> ' . __('Edit'),
                    ['action' => 'edit', $user->get('id')],
                    ['class' => 'dropdown-item', 'escape' => false]
                );
                $menuItems[] = 'divider';

                // delete user
                $menuItems[] = $this->Html->link(
                    '<i class="fa fa-trash-o"></i> ' . h(__('Delete')),
                    [
                        'plugin' => 'admin',
                        'controller' => 'Users',
                        'action' => 'delete',
                        $user->get('id'),
                    ],
                    ['class' => 'dropdown-item', 'escape' => false]
                );
            }
            if ($menuItems) {
                $panel .= $this->Layout->dropdownMenuButton(
                    $menuItems,
                    [
                        'class' => 'btn btn-link',
                    ]
                );
            }
        }
        if ($panel) { ?>
            <div class="card-footer"><?= $panel ?></div>
            <?php
        }
        ?>
    </div>
    <?php
    if ($modLocking) { ?>
        <div class="card mb-3">
            <div class="card-header">
                <?= $this->Layout->panelHeading(__('user.block.history')) ?>
            </div>
            <div class="card-body">
                <?=
                $this->element(
                    'users/block-report',
                    ['UserBlock' => $user->get('user_blocks')]
                );
                ?>
            </div>
            <?php
            if (!$user->get('user_lock')) {
                $defaultValue = 86400;
                $lock[] = $this->Form
                    ->create(
                        $blockForm,
                        ['url' => ['action' => 'lock'], 'id' => 'blockForm']
                    );
                $lock[] = $this->Form->button(
                    __('Block User'),
                    [
                        'class' => 'btn btn-link',
                        'type' => 'submit'
                    ]
                );
                $lock[] = "&nbsp;";
                $lock[] = $this->Form->control(
                    'lockRange',
                    [
                        'templates' => ['inputContainer' => '{{content}}'],
                        'label' => false,
                        'max' => 432000,
                        'min' => 21600,
                        'step' => 21600,
                        'style' => 'vertical-align: middle;',
                        'type' => 'range',
                        'value' => $defaultValue
                    ]
                );
                $lock[] = $this->Form->hidden(
                    'lockPeriod',
                    ['id' => 'lockPeriod', 'value' => $defaultValue]
                );
                $lock[] = $this->Form->unlockField('lockPeriod');
                $lock[] = $this->Form->hidden(
                    'lockUserId',
                    ['value' => $user->get('id')]
                );
                $lock[] = $this->Html->tag(
                    'span',
                    __('user.block.hours', ['hours' => $defaultValue / 3600]),
                    ['id' => 'lockTimeGauge', 'style' => 'padding: 0.5em']
                );
                $lock[] = $this->Form->end();
                ?>
                <div class="card-footer">
                    <?= implode('', $lock) ?>
                    <script>
                        SaitoApp.callbacks.afterAppInit.push(function () {
                            var BlockTimeGaugeView = Marionette.View.extend({
                                events: {'input #lockrange': '_onRangeChange'},
                                _onRangeChange: function (event) {
                                    var value = event.target.value;
                                    var l10n = $.i18n.__('user.block.hours', {hours: value / 3600});
                                    event.preventDefault();
                                    if (value === event.target.max) {
                                        l10n = '∞';
                                        value = 0;
                                    }
                                    this.$('#lockTimeGauge').html(l10n);
                                    this.$('#lockPeriod').attr('value', value);
                                }
                            });
                            new BlockTimeGaugeView({el: '#blockForm'});
                        });
                    </script>
                </div>
                <?php
            }
            ?>
        </div>
        <?php
    }
    ?>
</div>

<script type="text/template" id="tpl-recentposts">
    <div class="panel">
        <div class="panel-content">
            <?php
            if (empty($lastEntries)) {
                $this->element(
                    'generic/no-content-yet',
                    ['message' => __('No entries created yet.')]
                );
            } else {
                $threads = [];
                foreach ($lastEntries as $entry) {
                    $threads[] = $this->Posting->renderThread(
                        $entry,
                        ['ignore' => false]
                    );
                }
                echo $this->Html->nestedList(
                    $threads,
                    ['class' => 'threadCollection-node root']
                );
            }
            ?>
        </div>
        <?php
        if ($hasMoreEntriesThanShownOnPage) {
            $panel = $this->Html->link(
                __('Show all'),
                $urlToHistory,
                ['class' => 'panel-footer-form-bnt']
            );

            echo $this->Html->div('', $panel);
        }
        ?>
    </div>
</script>

<div class="js-rgUser" data-id="<?= $user->get('id') ?>"></div>
