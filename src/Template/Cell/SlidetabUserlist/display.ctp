<?php $this->start('slidetab-tab-button'); ?>
<div class="btn-slidetabUserlist">
    <div id="slidetabUserlist-counter" class='slidetab-tab-info'>
        <div class="slidetabUserlist-counter-inner">
            <?= $registered ?>
        </div>
    </div>
    <i class="fa fa-users fa-lg"></i>
</div>
<?php $this->end('slidetab-tab-button'); ?>
<?php $this->start('slidetab-content'); ?>
<div class="slidetab-header">
    <h4>
        <?=
        __(
            '{0} online ({1})',
            [
                $this->Html->link(__('user_area_linkname'), '/users/index'),
                $registered
            ]
        );
?>
    </h4>
</div>
<div class="slidetab-content">
    <ul class="slidetab-list">
        <?php foreach ($online as $userOnline) :
            $user = $userOnline->user;
            ?>
            <li>
                <?php // for performance reasons we don't use $this->Html->link() here
                ?>
                <a href="<?= $this->request->getAttribute('webroot'); ?>users/view/<?= $user->get('id') ?>"
                   class="<?= ($user->get('id') == $CurrentUser->getId()) ? 'slidetab-actUser' : '' ?>">
                    <?php
                    $role = $user->getRole();
                    $title = $this->Permissions->roleAsString($role);
                    switch ($role) {
                        case ('owner'):
                            $icon = 'fa-star-o';
                            break;
                        case ('admin'):
                            $icon = 'fa-admin';
                            break;
                        case ('mod'):
                            $icon = 'fa-mod';
                            break;
                        default:
                            $icon = 'fa-user';
                    }
                    ?>
                    <span class="slidetab-userlist-icon" title="<?= $title ?>">
                        <i class="fa <?= $icon ?>"></i>
                    </span>
                    <?= h($user->get('username')) ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</div>
<?php $this->end('slidetab-content'); ?>
<?= $this->element('Cell/slidetabs'); ?>
